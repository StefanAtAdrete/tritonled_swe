# Production Deploy - TritonLED

**Datum:** 2026-03-10  
**Uppdaterad:** 2026-04-23
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

## ⚠️ KRITISKT: CSS aggregering måste återställas vid deploy

**Problem:** Drupal aggregerar CSS/JS-filer i `sites/default/files/css/`. När ny CSS deployas via `git pull` regenereras INTE de aggregerade filerna automatiskt av `drush cr` — de gamla cachade bundlarna används fortfarande.

**Symptom:** Ny CSS (t.ex. hover-effekter, typografistil) syns inte på produktion trots att filen finns i repot och `drush cr` körts.

**Lösning:**
```bash
vendor/bin/drush config:set system.performance css.preprocess 0 -y
vendor/bin/drush cr
# Ladda om sidan och verifiera CSS
vendor/bin/drush config:set system.performance css.preprocess 1 -y
vendor/bin/drush cr
```

Alternativt:
```bash
rm -rf web/sites/default/files/css/*
rm -rf web/sites/default/files/js/*
vendor/bin/drush cr
```

⚠️ `rm -rf` på `files/css/` och `files/js/` är säkert — Drupal regenererar dem automatiskt.

**Regel:** Lägg alltid till CSS-aggregeringsrensning i deploy-rutinen när temat ändrats.

---

## ⚠️ KRITISKT: Custom block content följer INTE med i deploy

**Problem:** `block_content`-entiteter är *innehåll* (inte config) och synkas aldrig via `cim`.
Block-*placeringar* (region, synlighet) följer med — men inte *innehållet* i blocket.

**Symptom på prod:** "This block is broken or missing."

### Lösning A — Använd Views med Custom text (rekommenderas)
Views är ren config och exporteras/importeras via `cex`/`cim`.

### Lösning B — Återskapa block_content manuellt på prod (nödlösning)
```bash
vendor/bin/drush php:eval "
\$block = \Drupal\block_content\Entity\BlockContent::create([...]);
\$block->save();
"
vendor/bin/drush cr
```

---

## ⚠️ JS-cache efter deploy

**Symptom:** Ny JavaScript-behavior fungerar inte trots `drush cr`.
**Lösning:** Hård reload: `Cmd+Shift+R` (Mac) / `Ctrl+Shift+R` (Win/Linux).

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

---

## Deploy-workflow (uppdatering) ← ANVÄND DENNA

```bash
# 1. LOKALT — exportera config och pusha
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Beskrivning"
git push origin main

# 2. PRODUKTION — hämta, importera, rensa
ssh tritonled
cd /home/tritonled/htdocs/tritonled.se
git pull
vendor/bin/drush cim --partial -y
vendor/bin/drush cr

# 3. PRODUKTION — rensa CSS/JS-aggregering om temat ändrats
rm -rf web/sites/default/files/css/*
rm -rf web/sites/default/files/js/*
vendor/bin/drush cr

# 4. LOKALT — synka mediefiler om nya bilder laddats upp
rsync -avz --update --progress \
  /Users/steffes/Projekt/tritonled/web/sites/default/files/ \
  tritonled:/home/tritonled/htdocs/tritonled.se/web/sites/default/files/
```

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
Skapa manuellt (se mall i original-dokumentationen).

### 4. DB — exportera lokalt och importera på VPS
```bash
ddev drush sql:dump --result-file=./tritonled-export.sql
# Ladda upp via CloudPanel File Manager → /home/tritonled/tmp/
mysql -u adtriswe -p tritonswe < /home/tritonled/tmp/tritonled-export.sql
```

### 5. Mediefiler — synka via rsync
```bash
rsync -avz --update --progress \
  /Users/steffes/Projekt/tritonled/web/sites/default/files/ \
  tritonled:/home/tritonled/htdocs/tritonled.se/web/sites/default/files/
```

---

## Kända problem & lärdomar

### CSS aggregering på prod
- `drush cr` rensar INTE aggregerade CSS/JS-bundlar
- Måste köra `rm -rf files/css/* files/js/*` + `drush cr` efter temaändringar
- Alternativt: stäng av `css.preprocess` tillfälligt, ladda sidan, slå på igen

### rsync — "failed to set times"
- Status 23 + `failed to set times on` är **ofarligt** — rättighetsbegränsning på kataloger
- Filerna kopieras korrekt ändå
- Använd alltid `--update` flaggan för att inte skriva över nyare serverfiler

### rsync — SSH-nyckel
- Använd SSH-aliaset `tritonled` istället för `tritonled@168.231.108.87`
- `tritonled:` i rsync-sökvägen = SSH-aliaset från `~/.ssh/config`

### Custom block content på prod
- Följer INTE med i `cim` — är innehåll, inte config
- Använd Views med Custom text för statiska block

### JS-cache på prod
- `drush cr` rensar server-cache men inte browser-cache
- Hård reload: `Cmd+Shift+R` / `Ctrl+Shift+R`

### DB-dump via DDEV
- Använd `--result-file=./tritonled-export.sql` (inte `/tmp/`)

### trusted_host_patterns
- Måste uppdateras i `settings.php` när ny domän läggs till
