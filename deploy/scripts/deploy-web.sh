#!/usr/bin/env bash
# QBazaar — Web (Next.js) deploy.
#
# Invoked by .github/workflows/deploy-web.yml via SSH on the VPS.
# Builds the Next.js app in-place and pm2-restarts the qbazaar-web process.

set -euo pipefail
shopt -s inherit_errexit

REPO_DIR="${REPO_DIR:-/home/sanad/qbazaar}"
WEB_DIR="$REPO_DIR/qbazaar-web"
BRANCH="${DEPLOY_BRANCH:-production}"

log() { printf '\n\033[1;36m▶ %s\033[0m\n' "$*"; }

cd "$REPO_DIR"

log "Fetching origin"
git fetch origin --prune

log "Resetting to origin/$BRANCH"
git reset --hard "origin/$BRANCH"

cd "$WEB_DIR"

# ── Install + build ────────────────────────────────────────────────────────
log "npm ci (production install)"
npm ci --no-audit --no-fund

log "next build"
NEXT_TELEMETRY_DISABLED=1 npm run build

# ── Restart ────────────────────────────────────────────────────────────────
if pm2 describe qbazaar-web >/dev/null 2>&1; then
    log "Reloading PM2 process"
    pm2 reload qbazaar-web --update-env
else
    log "Starting PM2 process for the first time"
    pm2 start npm --name qbazaar-web -- start
    pm2 save
fi

# ── Sanity probe ───────────────────────────────────────────────────────────
log "Sanity probe (frontend home)"
sleep 2
STATUS=$(curl -fsS -m 5 -o /dev/null -w "%{http_code}" https://qbazzar.miete.site/ || echo "000")
echo "  qbazzar.miete.site => $STATUS"
if [[ "$STATUS" != "200" && "$STATUS" != "307" && "$STATUS" != "308" ]]; then
    echo "Frontend probe failed (expected 200/307/308, got $STATUS). See pm2 logs qbazaar-web."
    exit 1
fi

log "Deploy complete."
