# MADD Website — Production Deployment

Brand: **مدد للاتصالات / MADD Telecommunications**  
Domains: `https://madd.ps` (marketing) · `https://my.madd.ps` (portal + API)  
Server: Ubuntu 24.04 · hostname `madd` · LAN `12.12.12.168`

---

## Architecture (do not violate)

```text
Internet
   → Cloudflare
   → Public IP 84.242.50.172
   → Host Nginx :80 / :443   ← owns public HTTP(S)
   → 127.0.0.1:8080          ← MADD website stack ONLY
```

| Service | Hostname | Notes |
|---------|----------|--------|
| Marketing site | `madd.ps`, `www.madd.ps` | React `dist/` + `/api/site/*` |
| Portal + mobile API | `my.madd.ps` | Full Laravel (`portal/`) |
| Panel alias (optional) | `panel.madd.ps` | Same Laravel as `my` |
| UISP | `uisp.madd.ps` | `127.0.0.1:9443` — **do not touch** |
| GenieACS | `acs.madd.ps` | `:3000` / TR-069 `:7547` — **do not touch** |

**Never** bind the website to `0.0.0.0:80` or `:443`.  
**Never** stop/recreate UISP, GenieACS, MediaMTX, or their Docker networks (`unms_*`, `genieacs_*`, `mediamtx_*`).

Website Docker network: **`madd_web`** (isolated).

---

## What runs on `:8080`

Docker Compose: `deploy/docker/docker-compose.yml`

- `web` (Nginx) → published as **`127.0.0.1:8080`**
- `php` (PHP-FPM 8.3) → internal only

Routing inside the container (by `Host`):

- `madd.ps` → React + `/api/site/*`; old `/my` `/login` `/agent` → **301 to my.madd.ps**
- `my.madd.ps` → full Laravel (customer, agent, staff, `/api` for the app)
- `panel.madd.ps` → same Laravel (optional alias)
- React site CMS stays at `https://madd.ps/admin` (no conflict with Laravel `/admin/*` on `my`)

---

## One-time server setup

### 1. Code on disk

```bash
sudo mkdir -p /var/www/madd
# clone or rsync project into /var/www/madd
cd /var/www/madd
```

### 2. MySQL database

Use **host MySQL** (or an existing DB server).

```bash
sudo mysql -e "CREATE DATABASE madd_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'madd'@'127.0.0.1' IDENTIFIED BY 'STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL ON madd_portal.* TO 'madd'@'127.0.0.1'; FLUSH PRIVILEGES;"
```

From Docker PHP use `DB_HOST=host.docker.internal` with:

```yaml
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### 3. Environment files

```bash
cp .env.production.example .env
cp portal/.env.production.example portal/.env
# edit portal/.env:
#   APP_URL=https://my.madd.ps
#   SESSION_DOMAIN=.madd.ps
#   SANCTUM_STATEFUL_DOMAINS=my.madd.ps,madd.ps,www.madd.ps,panel.madd.ps
#   DB_* secrets
# edit root .env:
#   VITE_PORTAL_URL=https://my.madd.ps
cd portal && php artisan key:generate && cd ..
```

### 4. Host Nginx

```bash
sudo cp deploy/nginx/madd.ps.conf /etc/nginx/sites-available/madd.ps
sudo cp deploy/nginx/my.madd.ps.conf /etc/nginx/sites-available/my.madd.ps
sudo ln -sf /etc/nginx/sites-available/madd.ps /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/my.madd.ps /etc/nginx/sites-enabled/
# Optional: panel.madd.ps alias
# sudo cp deploy/nginx/panel.madd.ps.conf /etc/nginx/sites-available/panel.madd.ps
# sudo ln -sf /etc/nginx/sites-available/panel.madd.ps /etc/nginx/sites-enabled/

# SSL: Origin cert must cover my.madd.ps (or use *.madd.ps wildcard)
sudo nginx -t && sudo systemctl reload nginx
```

**Do not** edit UISP / GenieACS site files while enabling these.

### 5. Cloudflare

- DNS A/AAAA for `madd.ps`, `www.madd.ps`, **`my.madd.ps`** → orange-cloud to `84.242.50.172`
- SSL/TLS: **Full (strict)** with Origin Certificate that includes `my.madd.ps` (or `*.madd.ps`)
- Always Use HTTPS: On

### 6. Deploy / update

```bash
chmod +x deploy/scripts/deploy.sh
./deploy/scripts/deploy.sh
```

Verify on the server:

```bash
curl -I -H 'Host: madd.ps' http://127.0.0.1:8080/
curl -s -H 'Host: madd.ps' http://127.0.0.1:8080/api/site/config | head
curl -I -H 'Host: my.madd.ps' http://127.0.0.1:8080/my/login
curl -s -H 'Host: my.madd.ps' http://127.0.0.1:8080/api | head
```

Externally: `https://madd.ps` · `https://my.madd.ps/my/login`

---

## URL map

| URL | App |
|-----|-----|
| `https://madd.ps` | Marketing site (React) |
| `https://madd.ps/admin` | Site CMS (React) |
| `https://madd.ps/api/site/config` | Site CMS API |
| `https://my.madd.ps/my/login` | Subscriber portal |
| `https://my.madd.ps/login` | Staff / agents |
| `https://my.madd.ps/api` | Mobile / customer API |
| `https://uisp.madd.ps` | UISP (unchanged) |
| `https://acs.madd.ps` | GenieACS (unchanged) |

Legacy redirects: `https://madd.ps/my/*` → `https://my.madd.ps/my/*`

---

## Safety checklist

- [ ] Website listens on **`127.0.0.1:8080` only**
- [ ] Host Nginx still owns **80/443**
- [ ] `my.madd.ps` DNS + SSL enabled
- [ ] UISP still on **9443** behind its own vhost
- [ ] GenieACS ports **3000 / 7547 / 7557 / 7567** untouched
- [ ] Docker networks **unms_***, **genieacs_***, **mediamtx_*** untouched
- [ ] New network is only **`madd_web`**
- [ ] Server LAN IP **`12.12.12.168`** not changed by this app
