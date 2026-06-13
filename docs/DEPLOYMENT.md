# SimpCommerce — Deployment Guide

> **Stack**: Laravel 13 · PHP 8.4 · PostgreSQL 16 · Redis 7  
> **Auth**: Sanctum (token-based) + Google OAuth  
> **Queue**: Redis (database fallback)  
> **Cache**: Redis (database fallback)  

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Environment Configuration](#2-environment-configuration)
3. [Option A: Docker Deployment (Recommended)](#3-option-a-docker-deployment-recommended)
4. [Option B: Manual Deployment (Ubuntu)](#4-option-b-manual-deployment-ubuntu)
5. [Post-Deployment Checklist](#5-post-deployment-checklist)
6. [SSL / HTTPS](#6-ssl--https)
7. [Backup & Maintenance](#7-backup--maintenance)
8. [Scaling](#8-scaling)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Prerequisites

### Docker Deployment
- Docker Engine 24+
- Docker Compose v2
- 2 GB RAM minimum (4 GB recommended)

### Manual Deployment
- Ubuntu 24.04 LTS
- PHP 8.4+ with extensions: `pdo_pgsql`, `mbstring`, `intl`, `bcmath`, `pcntl`, `redis`, `gd`, `zip`
- Composer 2
- PostgreSQL 16+
- Redis 7
- Nginx
- Supervisor

---

## 2. Environment Configuration

### 2.1 Clone the Repository

```bash
git clone https://github.com/VenuxAK/Simp-eCommerce-API
cd Simp-eCommerce-API
```

### 2.2 Configure Environment

```bash
cp .env.production.example .env
nano .env
```

Key variables to configure:

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Generate with `php artisan key:generate` | `base64:...` |
| `APP_URL` | Public API URL | `https://api.example.com` |
| `ADMIN_URL` | Dashboard SPA URL | `https://admin.example.com` |
| `STOREFRONT_URL` | Storefront Nuxt URL | `https://store.example.com` |
| `DB_DATABASE` | PostgreSQL database name | `simp_commerce` |
| `DB_USERNAME` | PostgreSQL user | `simpcommerce` |
| `DB_PASSWORD` | PostgreSQL password | (use a strong password) |
| `MAIL_*` | SMTP credentials for transactional emails | — |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | — |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret | — |
| `SANCTUM_STATEFUL_DOMAINS` | SPA domains for cookie auth | `admin.example.com,store.example.com` |
| `CORS_ALLOWED_ORIGINS` | CORS origins (overrides ADMIN_URL + STOREFRONT_URL) | `https://admin.example.com,https://store.example.com` |
| `NIGHTWATCH_TOKEN` | Laravel Nightwatch monitoring token | — |

### 2.3 Generate App Key

```bash
php artisan key:generate
```

---

## 3. Option A: Docker Deployment (Recommended)

### 3.1 Start Services

```bash
docker compose up -d
```

This starts four containers:
- **`simpcommerce-app`** — PHP-FPM + Nginx (serves the API)
- **`simpcommerce-queue`** — Laravel queue worker (processes emails, imports, backups)
- **`simpcommerce-db`** — PostgreSQL 16
- **`simpcommerce-redis`** — Redis 7 (cache, sessions, queue)

### 3.2 Run Migrations & Seed

```bash
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

### 3.3 Verify

```bash
curl http://localhost/api/storefront/settings
# → {"data":{"name":"Main Store", ...}}
```

### 3.4 Useful Commands

```bash
# View logs
docker compose logs -f app

# Run artisan commands
docker compose exec app php artisan <command>

# Restart queue after code changes
docker compose restart queue

# Rebuild images after code changes
docker compose build --no-cache app
docker compose up -d
```

### 3.5 File Permissions

Uploaded images and backups persist in named volumes:
- `storage_data` — `/var/www/html/storage` (images, backups, logs)
- `pgdata` — `/var/lib/postgresql/data`
- `redis_data` — `/data`

To access files directly:
```bash
docker compose run --rm app ls -la /var/www/html/storage/app/public
```

---

## 4. Option B: Manual Deployment (Ubuntu)

### 4.1 Install Dependencies

```bash
# PHP 8.4 + extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4-fpm php8.4-pgsql php8.4-mbstring php8.4-intl \
    php8.4-bcmath php8.4-xml php8.4-curl php8.4-zip php8.4-redis php8.4-gd

# PostgreSQL
sudo apt install -y postgresql-16 postgresql-client-16

# Redis
sudo apt install -y redis-server

# Nginx
sudo apt install -y nginx

# Supervisor
sudo apt install -y supervisor

# Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

### 4.2 Configure PostgreSQL

```bash
sudo -u postgres psql -c "CREATE USER simpcommerce WITH PASSWORD 'your-strong-password';"
sudo -u postgres psql -c "CREATE DATABASE simp_commerce OWNER simpcommerce;"
```

### 4.3 Deploy Application

```bash
cd /var/www
git clone https://github.com/your-org/simpcommerce-api.git
cd simpcommerce-api

composer install --no-dev --optimize-autoloader --no-interaction

cp .env.production.example .env
nano .env   # Configure database, mail, etc.
php artisan key:generate
php artisan storage:link

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 4.4 Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/simpcommerce
```

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/simpcommerce-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location /storage/ {
        expires max;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/simpcommerce /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4.5 Run Migrations & Seed

```bash
php artisan migrate --seed
```

### 4.6 Configure Queue Worker (Supervisor)

```bash
sudo nano /etc/supervisor/conf.d/simpcommerce-queue.conf
```

```ini
[program:simpcommerce-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/simpcommerce-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/simpcommerce-api/storage/logs/queue-worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### 4.7 Configure Scheduled Tasks (Cron)

```bash
sudo crontab -u www-data -e
```

Add:

```
* * * * * php /var/www/simpcommerce-api/artisan schedule:run >> /dev/null 2>&1
```

### 4.8 Verify

```bash
curl http://api.example.com/api/storefront/settings
# → {"data":{"name":"Main Store", ...}}
```

---

## 5. Post-Deployment Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `CORS_ALLOW_LOCALHOST=false`
- [ ] `SESSION_DRIVER=database` or `redis` (not `file`)
- [ ] `CACHE_STORE=redis` or `database` (not `file`)
- [ ] `QUEUE_CONNECTION=redis` or `database` (not `sync`)
- [ ] Queue worker is running (verify: `supervisorctl status`)
- [ ] Storage symlink created (`php artisan storage:link`)
- [ ] OAuth redirect URI matches production domain
- [ ] CORS origins include ALL frontend domains
- [ ] Sanctum stateful domains configured for SPA auth
- [ ] Nightwatch monitoring enabled (if using)
- [ ] Database backups scheduled (see §7)
- [ ] SSL certificate installed (see §6)
- [ ] Firewall configured (allow 80, 443, block 5432, 6379)

---

## 6. SSL / HTTPS

### Using Certbot (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.example.com
```

This auto-configures Nginx with the certificate and sets up renewal.

### Verify Renewal

```bash
sudo certbot renew --dry-run
```

---

## 7. Backup & Maintenance

### 7.1 Database Backup

The API includes a built-in backup system:

```bash
# Via API (admin only)
curl -X POST https://api.example.com/api/backups \
  -H "Authorization: Bearer <admin-token>"

# List backups
curl https://api.example.com/api/backups \
  -H "Authorization: Bearer <admin-token>"
```

### 7.2 Scheduled Backups (Cron)

Add to crontab:

```
0 2 * * * php /var/www/simpcommerce-api/artisan backup:run >> /dev/null 2>&1
```

Creates a driver-aware backup (`pg_dump` for PostgreSQL, `mysqldump` for MySQL, file copy for SQLite).

### 7.3 Automated Backup with Docker

```bash
# Backup script
docker compose exec -T db pg_dump -U simpcommerce simp_commerce > backup-$(date +%Y%m%d).sql

# Restore
cat backup.sql | docker compose exec -T db psql -U simpcommerce simp_commerce
```

### 7.4 Log Rotation

Logs are stored in `storage/logs/`. Laravel's built-in log rotation handles daily files.
For Supervisor logs, add to `/etc/logrotate.d/simpcommerce`:

```
/var/www/simpcommerce-api/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
}
```

---

## 8. Scaling

### 8.1 Multiple Queue Workers

In Docker:
```yaml
# docker-compose.yml — scale queue workers
services:
  queue:
    deploy:
      replicas: 3
```

In Supervisor:
```ini
numprocs=4   # Run 4 concurrent queue workers
```

### 8.2 Database Read Replicas

Configure a read replica in `.env`:
```
DB_HOST_READ=replica.example.com
```

Laravel automatically routes read queries to the replica when configured.

### 8.3 Horizontal Scaling

For multiple API instances behind a load balancer:
- Use Redis for sessions, cache, and queue (shared across instances)
- Use a shared filesystem (S3, NFS) for uploaded images
- The API is stateless — any instance can handle any request

---

## 9. Troubleshooting

### 9.1 502 Bad Gateway (Nginx → PHP-FPM)

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm
# Check Nginx error log
sudo tail -f /var/log/nginx/error.log
```

### 9.2 Queue Jobs Not Processing

```bash
# Check supervisor status
sudo supervisorctl status
# Restart queue
sudo supervisorctl restart simpcommerce-queue:*
# Check for failed jobs
php artisan queue:failed
```

### 9.3 CORS Errors (Browser)

```
Access to XMLHttpRequest at 'https://api.example.com/...' from origin '...' has been blocked by CORS
```

- Verify `CORS_ALLOWED_ORIGINS` includes the request origin
- For multiple origins, use comma-separated list
- For development with dynamic ports, keep `CORS_ALLOW_LOCALHOST=true`

### 9.4 Sanctum Auth Errors

```bash
# Verify stateful domains are correctly set
php artisan config:show sanctum.stateful
# Verify CSRF cookie endpoint responds
curl -c cookies.txt https://api.example.com/sanctum/csrf-cookie
```

### 9.5 Mail Not Sending

```bash
# Check mail config
php artisan config:show mail
# Test mail delivery
php artisan tinker --execute 'Mail::raw("Test", fn($m) => $m->to("test@example.com")->subject("Test"));'
```
