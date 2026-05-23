#!/usr/bin/env bash
# QBazaar — one-time VPS bootstrap.
#
# Run as a sudo-capable user (sanad on the Miete VPS). The script is
# idempotent — re-running it does no harm.
#
# Usage:
#   curl -fsSL https://raw.githubusercontent.com/Qbazzar/Qbazaar/production/deploy/vps-bootstrap.sh | bash
# OR:
#   scp deploy/vps-bootstrap.sh sanad@147.79.115.44:/tmp/
#   ssh sanad@147.79.115.44 'bash /tmp/vps-bootstrap.sh'

set -euo pipefail

log() { printf '\n\033[1;36m▶ %s\033[0m\n' "$*"; }

# ── 1. System packages ──────────────────────────────────────────────────────
log "Updating apt + installing system deps"
sudo apt-get update -y
sudo apt-get upgrade -y
sudo apt-get install -y \
    software-properties-common ca-certificates curl gnupg lsb-release \
    git zip unzip jq \
    nginx supervisor certbot python3-certbot-nginx \
    redis-server \
    build-essential

# ── 2. PHP 8.3 + extensions ────────────────────────────────────────────────
log "Installing PHP 8.3 + extensions"
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y
sudo apt-get install -y \
    php8.3 php8.3-fpm php8.3-cli \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-mysql php8.3-redis php8.3-bcmath php8.3-intl \
    php8.3-gd php8.3-imagick \
    php8.3-pcntl php8.3-posix

# ── 3. Composer ─────────────────────────────────────────────────────────────
if ! command -v composer >/dev/null 2>&1; then
    log "Installing Composer"
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

# ── 4. Node 22 (NodeSource) ────────────────────────────────────────────────
if ! command -v node >/dev/null 2>&1 || [[ "$(node -v)" != v22* ]]; then
    log "Installing Node.js 22"
    curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

if ! command -v pm2 >/dev/null 2>&1; then
    log "Installing PM2 (process manager for Next.js)"
    sudo npm install -g pm2
fi

# ── 5. MySQL 8 ──────────────────────────────────────────────────────────────
if ! command -v mysql >/dev/null 2>&1; then
    log "Installing MySQL 8 server"
    sudo apt-get install -y mysql-server
    sudo systemctl enable --now mysql
fi

# ── 6. Meilisearch ──────────────────────────────────────────────────────────
if ! command -v meilisearch >/dev/null 2>&1; then
    log "Installing Meilisearch as a systemd service"
    curl -L https://install.meilisearch.com | sh
    sudo mv meilisearch /usr/local/bin/
    sudo useradd -d /var/lib/meilisearch -s /bin/false -r meilisearch 2>/dev/null || true
    sudo mkdir -p /var/lib/meilisearch /etc/meilisearch
    sudo chown -R meilisearch:meilisearch /var/lib/meilisearch

    cat <<'EOF' | sudo tee /etc/systemd/system/meilisearch.service > /dev/null
[Unit]
Description=Meilisearch
After=network.target

[Service]
Type=simple
User=meilisearch
Group=meilisearch
ExecStart=/usr/local/bin/meilisearch --db-path /var/lib/meilisearch --http-addr 127.0.0.1:7700 --env production
Restart=always

[Install]
WantedBy=multi-user.target
EOF
    sudo systemctl daemon-reload
    sudo systemctl enable --now meilisearch
fi

# ── 7. Redis ────────────────────────────────────────────────────────────────
sudo systemctl enable --now redis-server

# ── 8. Nginx ────────────────────────────────────────────────────────────────
sudo systemctl enable --now nginx

# ── 9. Verify ───────────────────────────────────────────────────────────────
log "Versions installed"
php --version | head -n 1
composer --version
node --version
npm --version
mysql --version
nginx -v 2>&1
redis-cli --version
meilisearch --version || true

log "Bootstrap complete."
cat <<'EOF'

Next steps (manual):
  1. Authorise the GitHub Actions SSH key:
       echo 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIF1RAUBQxxp8FO3PtcPRP862nga/UWwYTqZCcaElvufM github-actions@qbazzar.miete.site' \
         >> /home/sanad/.ssh/authorized_keys
       chmod 600 /home/sanad/.ssh/authorized_keys
  2. Create the production MySQL database + user:
       sudo mysql -e "CREATE DATABASE qbazaar_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
       sudo mysql -e "CREATE USER 'qbazaar'@'localhost' IDENTIFIED BY '<STRONG-PASSWORD>';"
       sudo mysql -e "GRANT ALL ON qbazaar_production.* TO 'qbazaar'@'localhost'; FLUSH PRIVILEGES;"
  3. Clone the repo:
       cd /home/sanad && git clone https://github.com/Qbazzar/Qbazaar.git qbazaar
       cd qbazaar && git switch production
  4. Drop in Nginx site configs + obtain SSL:
       sudo cp deploy/nginx/*.conf /etc/nginx/sites-available/
       sudo ln -sf /etc/nginx/sites-available/api.qbazzar.miete.site.conf /etc/nginx/sites-enabled/
       sudo ln -sf /etc/nginx/sites-available/qbazzar.miete.site.conf /etc/nginx/sites-enabled/
       sudo nginx -t && sudo systemctl reload nginx
       sudo certbot --nginx -d qbazzar.miete.site -d api.qbazzar.miete.site \
         --non-interactive --agree-tos -m ahmedjaberdev@gmail.com --redirect
  5. First build (see deploy/README.md → step 6).
EOF
