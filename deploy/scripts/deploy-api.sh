#!/usr/bin/env bash
# QBazaar — API deploy.
#
# Invoked by .github/workflows/deploy-api.yml via SSH on the VPS.
# Assumes the monorepo is checked out at /home/sanad/qbazaar with the
# 'production' branch tracking origin/production.

set -euo pipefail
shopt -s inherit_errexit

REPO_DIR="${REPO_DIR:-/home/sanad/qbazaar}"
API_DIR="$REPO_DIR/qbazaar-api"
BRANCH="${DEPLOY_BRANCH:-production}"

log() { printf '\n\033[1;36m▶ %s\033[0m\n' "$*"; }

cd "$REPO_DIR"

log "Fetching origin"
git fetch origin --prune

log "Resetting to origin/$BRANCH"
git reset --hard "origin/$BRANCH"
git submodule update --init --recursive || true

cd "$API_DIR"

# ── Maintenance mode ───────────────────────────────────────────────────────
if [[ -f artisan ]]; then
    log "Entering maintenance mode"
    php artisan down --render="errors::503" --refresh=15 --retry=15 --secret="deploy-$(date +%s)" || true
fi

cleanup() {
    if [[ -f artisan ]]; then
        php artisan up || true
    fi
}
trap cleanup EXIT

# ── Composer (production, no-dev, optimised) ───────────────────────────────
log "Composer install --no-dev --optimize-autoloader"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ── Migrations ─────────────────────────────────────────────────────────────
log "Running migrations"
php artisan migrate --force --no-interaction

# ── Caches ─────────────────────────────────────────────────────────────────
log "Re-building config / route / view caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

# ── Storage link ──────────────────────────────────────────────────────────
if [[ ! -L public/storage ]]; then
    log "Linking storage"
    php artisan storage:link
fi

# ── Queue + Scout reindex hook (no-op until Sprint 5) ─────────────────────
log "Restarting queue workers"
php artisan queue:restart || true

# ── Sanity probe ───────────────────────────────────────────────────────────
log "Sanity probe (/api/v1/health via php-fpm)"
sleep 1
HEALTH=$(curl -fsS -m 5 -o /dev/null -w "%{http_code}" https://api.qbazzar.miete.site/api/v1/health || echo "000")
echo "  health => $HEALTH"
if [[ "$HEALTH" != "200" ]]; then
    echo "Health probe failed (expected 200, got $HEALTH). Investigate logs at /var/log/nginx/api.qbazzar.error.log + storage/logs/laravel.log."
    exit 1
fi

log "Deploy complete."
