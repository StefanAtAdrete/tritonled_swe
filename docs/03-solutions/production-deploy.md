# Production Deploy - TritonLED

**Datum:** 2026-03-10  
**Status:** ✅ Klar — sajten live på preview.affarsfabriken.se

---

## Infrastruktur

| | Lokalt (DDEV) | Produktion (VPS) |
|---|---|---|
| **Server** | Mac mini / Docker | Hostinger VPS (srv808609) |
| **IP** | localhost | 168.231.108.87 |
| **Panel** | DDEV | CloudPanel (cp.affarsfabriken.se) |
| **OS** | macOS / Ubuntu (Docker) | Ubuntu 24.04 |
| **PHP** | 8.4 | 8.4 |
| **DB** | MariaDB 10.11 | MariaDB (CloudPanel) |
| **Webserver** | nginx-fpm | nginx (CloudPanel) |
| **Preview-URL** | tritonled.ddev.site | https://preview.affarsfabriken.se |

---

## Produktionsinställningar

### Databas
- **Namn:** tritonswe
- **Användare:** adtriswe
- **Host:** localhost
- **Port:** 3306

### Sökvägar på VPS
- **Webroot:** `/home/tritonled/htdocs/tritonled.se/web`
- **Projektrot:** `/home/tritonled/htdocs/tritonled.se`
- **Site user:** `tritonled`
- **Logs:** `/home/tritonled/logs/nginx/`

### Git-repo
- **URL:** https://github.com/StefanAtAdrete/tritonled_swe
- **Branch:** main

---

## settings.php (produktion)

Minimal `settings.php` på VPS — DDEV-specifika inställningar är INTE med:

```php
<?php

$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => 'tritonswe',
  'username' => 'adtriswe',
  'password' => '[se lösenord i CloudPanel → Databases]',
  'host' => 'localhost',
  'port' => '3306',
  'prefix' => '',
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
];

$settings['config_sync_directory'] = $app_root . '/../config/sync';
$settings['file_private_path'] = $app_root . '/../private';

$settings['trusted_host_patterns'] = [
  '^\d+\.\d+\.\d+\.\d+$',
  '^tritonled\.se$',
  '^www\.tritonled\.se$',
  '^preview\.affarsfabriken\.se$',
];

$settings['hash_salt'] = '[hash salt på servern]';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];
```

⚠️ `settings.php` är INTE i git (`.gitignore`) — måste skapas manuellt vid ny deploy.

---

## SSL / Domäner

| Domän | SSL | Status |
|---|---|---|
| `preview.affarsfabriken.se` | Let's Encrypt (giltig t.o.m. Jun 8, 2026) | ✅ Aktiv |
| `tritonled.se` | Self-signed | ⏳ Väntar på DNS-flytt |
| `www.tritonled.se` | Self-signed | ⏳ Väntar på DNS-flytt |

### DNS
- `preview.affarsfabriken.se` → A-record → `168.231.108.87` (hanteras i Hostinger hPanel)
- `tritonled.se` registrerad hos Loopia, DNS hanteras hos Hostinger

---

## Deploy-workflow (första gången)

### 1. Lokalt — förbered och pusha
```bash
cd /Users/steffes/Projekt/tritonled
ddev drush cex -y
ddev drush sql:dump --result-file=./tritonled-export.sql
git add -A
git commit -m "Deploy: [beskrivning]"
git push origin main
```

### 2. VPS — klona och installera
```bash
cd /home/tritonled/htdocs/tritonled.se
git clone https://github.com/StefanAtAdrete/tritonled_swe.git .
composer install --no-dev --optimize-autoloader
```

### 3. VPS — settings.php
Skapa manuellt (se mall ovan).

### 4. DB — exportera lokalt och importera på VPS
```bash
# Lokalt (Mac):
ddev drush sql:dump --result-file=./tritonled-export.sql

# Ladda upp via CloudPanel File Manager → /home/tritonled/tmp/
# Sedan på VPS:
mysql -u adtriswe -p tritonswe < /home/tritonled/tmp/tritonled-export.sql
```

### 5. Mediefiler — synka via rsync
```bash
# Från Mac (kräver SSH-lösenord):
rsync -avz --progress -o PreferredAuthentications=password \
  /Users/steffes/Projekt/tritonled/web/sites/default/files/ \
  root@168.231.108.87:/home/tritonled/htdocs/tritonled.se/web/sites/default/files/

# Rättigheter på VPS:
chown -R tritonled:tritonled /home/tritonled/htdocs/tritonled.se/web/sites/default/files
chmod -R 755 /home/tritonled/htdocs/tritonled.se/web/sites/default/files
```

### 6. VPS — cache och kontroll
```bash
vendor/bin/drush --root=/home/tritonled/htdocs/tritonled.se/web cr
vendor/bin/drush --root=/home/tritonled/htdocs/tritonled.se/web status
```

---

## Deploy-workflow (uppdatering)

```bash
# Lokalt — pusha ändringar
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Beskrivning"
git push origin main

# VPS — hämta och importera
cd /home/tritonled/htdocs/tritonled.se
git pull origin main
composer install --no-dev --optimize-autoloader
vendor/bin/drush --root=/home/tritonled/htdocs/tritonled.se/web updb -y
vendor/bin/drush --root=/home/tritonled/htdocs/tritonled.se/web cim -y
vendor/bin/drush --root=/home/tritonled/htdocs/tritonled.se/web cr
```

---

## Kända problem & lärdomar

### SSH-autentisering
- SSH-nyckel (`~/.ssh/id_ed25519`) har passphrase som Stefan inte minns
- Lösning: Använd `-o PreferredAuthentications=password` för rsync/scp
- Root-lösenord återställs via Hostinger hPanel → VPS → Settings → Root password

### DB-dump via DDEV
- ❌ `--result-file=/tmp/...` fungerar inte — `/tmp` är inuti Docker-containern
- ✅ Använd `--result-file=./tritonled-export.sql` (sparas i projektmappen)

### CloudPanel SSH
- CloudPanel-lösenord ≠ VPS root-lösenord
- Root-lösenord: Hostinger hPanel → VPS → Settings
- CloudPanel-inloggning: https://cp.affarsfabriken.se

### trusted_host_patterns
- Måste uppdateras i `settings.php` när ny domän/subdomän läggs till
- Efter ändring: kör `drush cr`

### Bildcacher
- `sites/default/files/styles/` regenereras automatiskt av Drupal vid första besök
- Behöver inte synkas manuellt

### jsonrpc deprecation-varning
- `Drupal\jsonrpc` ger PHP 8.4 deprecation-varning
- Är en dev-modul — bör avinstalleras på produktion (se ENVIRONMENT-STRATEGY.md)

---

## Nästa steg

- [ ] Avinstallera dev-moduler på produktion (jsonrpc, mcp_tools, field_ui etc.)
- [ ] Sätta upp SSH-nyckel utan passphrase för smidigare deploy
- [ ] Automatisera deploy-scriptet
- [ ] Flytta `tritonled.se` DNS till VPS när sajten är godkänd
- [ ] Let's Encrypt för `tritonled.se` och `www.tritonled.se` efter DNS-flytt
- [ ] Sätta upp cron för Feeds-import på produktion
