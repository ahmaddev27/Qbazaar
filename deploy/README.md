# QBazaar — Production Deploy

Everything needed to deploy QBazaar to the **Miete VPS**:

- VPS: `147.79.115.44` (sanad)
- Frontend domain: `https://qbazzar.miete.site` → Next.js on port 3000
- API/Backend domain: `https://api.qbazzar.miete.site` → Laravel via php-fpm + Nginx

Both apps live side-by-side under `/home/sanad/qbazaar/` (single git clone of this monorepo).

---

## 🌿 Branch model

| Branch | Purpose | Triggers |
|--------|---------|----------|
| `main` | Dev — every feature commit lands here first | CI runs Pint + PHPStan + tests + Next.js build |
| `production` | What's live on the VPS | Push → GitHub Actions auto-deploys |

Promote with `git switch production && git merge main && git push origin production`.

---

## 🛠️ One-time VPS bootstrap

Run the bootstrap script once on a fresh VPS — installs PHP 8.3, Composer, Node 22, MySQL, Redis, Meilisearch, Nginx, Supervisor, Certbot:

```bash
ssh sanad@147.79.115.44
curl -fsSL https://raw.githubusercontent.com/Qbazzar/Qbazaar/production/deploy/vps-bootstrap.sh | bash
```

(Or `scp deploy/vps-bootstrap.sh sanad@...:/tmp/` and run it locally — same result.)

After bootstrap:

1. **Authorise the GitHub Actions key** so future deploys can SSH in without a password:
   ```bash
   echo 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIF1RAUBQxxp8FO3PtcPRP862nga/UWwYTqZCcaElvufM github-actions@qbazzar.miete.site' \
     >> /home/sanad/.ssh/authorized_keys
   chmod 600 /home/sanad/.ssh/authorized_keys
   ```

2. **Clone the repo onto the VPS:**
   ```bash
   cd /home/sanad
   git clone https://github.com/Qbazzar/Qbazaar.git qbazaar
   cd qbazaar && git switch production
   ```

3. **Copy site configs into Nginx:**
   ```bash
   sudo cp /home/sanad/qbazaar/deploy/nginx/api.qbazzar.miete.site.conf /etc/nginx/sites-available/
   sudo cp /home/sanad/qbazaar/deploy/nginx/qbazzar.miete.site.conf /etc/nginx/sites-available/
   sudo ln -sf /etc/nginx/sites-available/api.qbazzar.miete.site.conf /etc/nginx/sites-enabled/
   sudo ln -sf /etc/nginx/sites-available/qbazzar.miete.site.conf /etc/nginx/sites-enabled/
   sudo nginx -t && sudo systemctl reload nginx
   ```

4. **Get SSL certs:**
   ```bash
   sudo certbot --nginx -d qbazzar.miete.site -d api.qbazzar.miete.site \
     --non-interactive --agree-tos -m ahmedjaberdev@gmail.com --redirect
   ```

5. **Production `.env` files** — copy `qbazaar-api/.env.example` → `qbazaar-api/.env` and fill it in with production secrets. Same for `qbazaar-web/.env.example` → `qbazaar-web/.env.production`.

6. **First app build:**
   ```bash
   cd /home/sanad/qbazaar/qbazaar-api
   composer install --no-dev --optimize-autoloader
   php artisan key:generate    # only if APP_KEY is empty
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache

   cd /home/sanad/qbazaar/qbazaar-web
   npm ci
   npm run build
   pm2 start npm --name qbazaar-web -- start
   pm2 save && pm2 startup    # run the printed command via sudo to persist
   ```

---

## 🤖 Auto-deploy (GitHub Actions)

Two workflows live in `.github/workflows/`:

| Workflow | Triggers when | Does |
|----------|---------------|------|
| `deploy-api.yml` | Push to `production` AND `qbazaar-api/**` changed | SSH → `deploy/scripts/deploy-api.sh` |
| `deploy-web.yml` | Push to `production` AND `qbazaar-web/**` changed | SSH → `deploy/scripts/deploy-web.sh` |

A push that only touches `qbazaar-contracts/` or docs **doesn't trigger a deploy** (path filters).

---

## 🔐 GitHub Secrets required

Add these in **`Settings → Secrets and variables → Actions`** for the `Qbazzar/Qbazaar` repo:

| Secret | Value | Where it's used |
|--------|-------|-----------------|
| `DEPLOY_HOST` | `147.79.115.44` | both workflows |
| `DEPLOY_USER` | `sanad` | both workflows |
| `DEPLOY_PORT` | `22` | both workflows |
| `DEPLOY_SSH_KEY` | Contents of `.deploy-keys/qbazaar_deploy` (the **private** key — see [Adding the secret](#adding-the-ssh-secret) below) | both workflows |

Optionally for production secrets that the `deploy-*.sh` scripts will template into `.env`:

| Secret | Value |
|--------|-------|
| `APP_KEY` | `base64:...` from `php artisan key:generate --show` |
| `DB_PASSWORD` | MySQL `qbazaar_prod` user password |
| `TWILIO_SID` / `TWILIO_TOKEN` / `TWILIO_FROM` | from Twilio Console |
| `SENTRY_LARAVEL_DSN` | from Sentry project |
| `FCM_*` | from Firebase Console |

The deploy script reads them at deploy time and writes them into `qbazaar-api/.env`. We never commit them.

---

## Adding the SSH secret

The SSH **private** key was generated locally (`.deploy-keys/qbazaar_deploy`) and is gitignored. To add it to GitHub:

```bash
# On your dev box:
cat /c/laragon/www/QB/.deploy-keys/qbazaar_deploy
```

Copy the entire output (`-----BEGIN OPENSSH PRIVATE KEY-----` to `-----END OPENSSH PRIVATE KEY-----`) and paste it as the `DEPLOY_SSH_KEY` secret value.

The **public** key (committed at `deploy/keys/github-actions.pub`) goes into `/home/sanad/.ssh/authorized_keys` on the VPS — see the bootstrap section above.

---

## Manual deploy (if Actions is down)

From your dev box:

```bash
# API
ssh sanad@147.79.115.44 "cd /home/sanad/qbazaar && bash deploy/scripts/deploy-api.sh"

# Web
ssh sanad@147.79.115.44 "cd /home/sanad/qbazaar && bash deploy/scripts/deploy-web.sh"
```

---

## Folder layout

```
deploy/
├── README.md                                ← you are here
├── vps-bootstrap.sh                         ← one-time server setup
├── keys/
│   └── github-actions.pub                   ← committed public key
├── nginx/
│   ├── api.qbazzar.miete.site.conf          ← reverse-proxies to php-fpm
│   └── qbazzar.miete.site.conf              ← reverse-proxies to Next.js on :3000
├── scripts/
│   ├── deploy-api.sh                        ← run by GitHub Actions on the VPS
│   └── deploy-web.sh
└── supervisor/
    ├── qbazaar-queue.conf                   ← Laravel queue worker
    └── qbazaar-reverb.conf                  ← Laravel Reverb (WebSocket) — wired in Sprint 8
```
