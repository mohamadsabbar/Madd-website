# MADD Website — Production Deployment

Brand: **مدد للاتصالات / MADD Telecommunications**  
Domain: `https://madd.ps` (± `www.madd.ps`)  
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
| MADD website | `madd.ps`, `www.madd.ps` | This project → `:8080` |
| ISP panel (Laravel) | `panel.madd.ps` (recommended) | Same `:8080`, different `Host` |
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

Routing inside the container:

- `madd.ps` / `www.madd.ps` → React `dist/` + `/api` + `/my` + `/login` → Laravel  
- `panel.madd.ps` → full Laravel (`portal/public`) for staff/MikroTik admin  
- React CMS stays at `https://madd.ps/admin` (no conflict with Laravel `/admin/*`)

---

## One-time server setup

### 1. Code on disk

```bash
sudo mkdir -p /var/www/madd
# clone or rsync project into /var/www/madd
cd /var/www/madd
```

### 2. MySQL database

Use **host MySQL** (or an existing DB server). Do not put MySQL on host ports that conflict with other stacks unless intentional.

```bash
sudo mysql -e "CREATE DATABASE madd_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'madd'@'127.0.0.1' IDENTIFIED BY 'STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL ON madd_portal.* TO 'madd'@'127.0.0.1'; FLUSH PRIVILEGES;"
# import dump if needed
```

From Docker PHP, `DB_HOST=host.docker.internal` may not exist on Linux — prefer:

- `DB_HOST=172.17.0.1` (docker0), or  
- run PHP with `network_mode: host` (not recommended), or  
- put MySQL on `madd_web` (optional, commented in compose).

Simplest production pattern: **bind MySQL to 127.0.0.1** and add to `php` service:

```yaml
extra_hosts:
  - "host.docker.internal:host-gateway"
```

Then `DB_HOST=host.docker.internal` in `portal/.env`.

### 3. Environment files

```bash
cp .env.production.example .env
cp portal/.env.production.example portal/.env
cd portal && php artisan key:generate && cd ..
# edit DB_* , APP_URL=https://madd.ps , secrets
```

### 4. Host Nginx

```bash
sudo cp deploy/nginx/madd.ps.conf /etc/nginx/sites-available/madd.ps
sudo cp deploy/nginx/panel.madd.ps.conf /etc/nginx/sites-available/panel.madd.ps
sudo ln -sf /etc/nginx/sites-available/madd.ps /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/panel.madd.ps /etc/nginx/sites-enabled/
# Install SSL certs at paths in those files (Cloudflare Origin or Let's Encrypt)
sudo nginx -t && sudo systemctl reload nginx
```

**Do not** edit UISP / GenieACS site files while enabling these.

### 5. Cloudflare

- DNS A/AAAA for `madd.ps`, `www.madd.ps` (and later `panel.madd.ps`) → orange-cloud to `84.242.50.172`
- SSL/TLS mode: **Full (strict)** with Origin Certificate, or Full with valid LE certs
- Always Use HTTPS: On

### 6. Deploy / update

```bash
chmod +x deploy/scripts/deploy.sh
./deploy/scripts/deploy.sh
```

Verify locally on the server:

```bash
curl -I -H 'Host: madd.ps' http://127.0.0.1:8080/
curl -s -H 'Host: madd.ps' http://127.0.0.1:8080/api/site/config | head
```

Then externally: `https://madd.ps`

---

## URL map after go-live

| URL | App |
|-----|-----|
| `https://madd.ps` | Marketing site (React) |
| `https://madd.ps/admin` | Site CMS (React) |
| `https://madd.ps/api/site/config` | Laravel CMS API |
| `https://madd.ps/my/login` | Subscriber portal |
| `https://panel.madd.ps/login` | Staff / agents (Laravel) |
| `https://uisp.madd.ps` | UISP (unchanged) |
| `https://acs.madd.ps` | GenieACS (unchanged) |

---

## Future hostnames (DNS only for now)

Prepare Cloudflare DNS when ready; **do not** collide with existing services:

`panel.madd.ps` · `api.madd.ps` · `router.madd.ps` · `status.madd.ps`  
(keep `uisp.madd.ps` / `acs.madd.ps` as-is)

---

## Safety checklist

- [ ] Website listens on **`127.0.0.1:8080` only**
- [ ] Host Nginx still owns **80/443**
- [ ] UISP still on **9443** behind its own vhost
- [ ] GenieACS ports **3000 / 7547 / 7557 / 7567** untouched
- [ ] Docker networks **unms_***, **genieacs_***, **mediamtx_*** untouched
- [ ] New network is only **`madd_web`**
- [ ] Server LAN IP **`12.12.12.168`** not changed by this app
- [ ] HTTP → HTTPS redirect works for `madd.ps`
