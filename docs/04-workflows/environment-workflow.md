# Environment Workflow & CI/CD

**Version**: 1.0
**Skapad**: 2026-04-21
**Syfte**: Förklara hur lokal miljö, Git och produktion hänger ihop — och vad som skapas var

---

## Den viktigaste regeln

```
Config (struktur)  →  flödar UPPÅT:   Lokal → Git → Prod
Content (data)     →  flödar NEDÅT:   Prod → Lokal (för referens)
```

**Config** = allt som är versionshanterat i `config/sync/` (fält, views, moduler, temaconfig)
**Content** = allt som lever i databasen (produkter, media, översättningar, Layout Builder-layouts)

Du bygger struktur lokalt. Du skapar innehåll på prod (eller lokalt med vetskapen att det aldrig deployeras automatiskt).

---

## Miljöerna

```
┌─────────────────────────────────────────────────────────┐
│  LOKAL (DDEV)                                            │
│  tritonled.ddev.site                                     │
│  Databas: lokal MySQL                                    │
│  Config: config/sync/ (YAML-filer i Git)                 │
│                                                          │
│  HÄR: Bygger struktur (fält, views, moduler, tema)       │
│  HÄR: Testar allt innan deploy                           │
└──────────────────┬──────────────────────────────────────┘
                   │
                   │  git push
                   ▼
┌─────────────────────────────────────────────────────────┐
│  GIT (GitHub)                                            │
│  StefanAtAdrete/tritonled_swe                            │
│                                                          │
│  Innehåller: kod, config/sync/, templates, moduler       │
│  Innehåller INTE: databas, uppladdade filer              │
└──────────────────┬──────────────────────────────────────┘
                   │
                   │  git pull + drush cim
                   ▼
┌─────────────────────────────────────────────────────────┐
│  PRODUKTION (Hostinger VPS / CloudPanel)                 │
│  tritonled.se                                            │
│  SSH: tritonled (168.231.108.87:2222)                    │
│                                                          │
│  HÄR: Lever det riktiga innehållet                       │
│  HÄR: Skapas produkter, media, översättningar            │
└─────────────────────────────────────────────────────────┘
```

---

## Vad skapas var?

### Skapas LOKALT (deployas sedan via Git → cim)

| Vad | Exempel |
|-----|---------|
| Fältdefinitioner | `field_product_sku`, `field_configurator_schema` |
| Content types / Product types | `led_luminaire_max_opti`, `floodlight` |
| Views | `featured_products`, `produktserier` |
| Image styles | `card_medium`, `product_responsive` |
| Responsive image styles | `hero_responsive` |
| Block-layout (config) | Block-typer, region-config |
| Modulinstallationer | `ddev drush en [modul]` |
| Tema-inställningar | `tritonled_radix.settings` |
| Roles & permissions | `elektriker`, `partner_gold` |
| Feeds-config | Feed types, mappings |
| Layout Builder display config | View modes, default layouts |

### Skapas på PROD (eller lokalt men deployas ALDRIG via config)

| Vad | Exempel |
|-----|---------|
| Produkter & variationer | Triton MAX, SROW-ED |
| Media & bilder | Produktbilder, hero-bilder |
| Översättningar (content) | Svenska/engelska produkttexter |
| Layout Builder-overrides | Per-produkt layoutanpassningar |
| Block content (basic blocks) | Textblock, sociala medier-block |
| Taxonomy terms | Produktkategorier, länder |
| Webform submissions | Offertförfrågningar |
| Orders | Offert-orders |
| Path aliases | Genereras av Pathauto på prod |

> **Kom ihåg**: Layout Builder per-produkt-overrides är **alltid** DB-content.
> De kan inte deployas via config och måste återskapas manuellt per miljö.

---

## Rutin: Dra ned prod-DB lokalt

Du gör detta för att se hur sajten ser ut med riktigt innehåll, eller för att felsöka.

### ⚠️ Kritiskt — gör detta EXAKT i denna ordning:

```bash
# 1. Importera prod-DB (via Backup & Migrate eller snapshot)
ddev snapshot restore [namn]
# eller via admin: importera .sql-fil

# 2. DIREKT EFTERÅT — kontrollera delta
ddev drush config:status

# 3. Importera config från YAML (YAML vinner över DB)
ddev drush cim --partial -y

# 4. Verifiera
ddev drush config:status
# Ska visa: No differences (eller bara "Only in active" för ny config)

# 5. Rensa cache
ddev drush cr
```

### Varför `--partial`?

`cim --partial` importerar bara det som finns i YAML-filerna.
`cim` (utan partial) skulle radera config som finns i DB men inte i YAML — farligt.

### Vad händer om du hoppar över steg 3?

Config i YAML-filerna (din lokala utveckling) saknas i den importerade prod-DB:n.
Om du sedan kör `cex` skriver Drupal prod-DB:ns config till YAML — och raderar din lokala config.
**Det är precis vad som hände 2026-04-21 (258 filer raderade).**

---

## Rutin: Deploy till produktion

### Förutsättning
- Allt lokalt arbete är testat och fungerar
- `config:status` visar `No differences` lokalt
- Git är pushad

### Deploy-steg

```bash
# På produktionsservern (SSH: tritonled)
cd /home/tritonled/htdocs/tritonled.se

# 1. Hämta senaste koden
git pull

# 2. Kontrollera config-delta
vendor/bin/drush config:status

# 3. Importera config (--partial för säkerhet)
vendor/bin/drush cim --partial -y

# 4. Kör eventuella databasuppdateringar
vendor/bin/drush updb -y

# 5. Rensa cache
vendor/bin/drush cr

# 6. Verifiera
vendor/bin/drush config:status
```

### Vad deployas?

| Deployas | Deployas INTE |
|----------|---------------|
| ✅ PHP-kod (moduler, tema, templates) | ❌ Databas-content |
| ✅ Config (YAML → `cim`) | ❌ Uppladdade filer (sites/default/files) |
| ✅ Composer-beroenden | ❌ Layout Builder-overrides |
| ✅ JS/CSS-assets | ❌ Block content |

---

## Rutin: Nytt fält eller struktur

Alltid lokalt. Aldrig direkt på prod.

```bash
# 1. Skapa fält via drush php:eval (ALDRIG skapa YAML manuellt)
ddev drush php:eval "..."

# 2. Exportera till config
ddev drush cex -y

# 3. Granska diff
git diff config/sync/

# 4. Commit
git add -A
git commit -m "[TASK-NNN] Lägg till field_xyz på produkt-bundle"

# 5. Push
git push

# 6. Deploy till prod
# (se deploy-rutin ovan)
```

---

## Rutin: Ny modul

```bash
# 1. Installera lokalt
ddev composer require drupal/[modul]
ddev drush en [modul] -y

# 2. Konfigurera i admin-UI lokalt

# 3. Exportera config
ddev drush cex -y

# 4. Commit & push (inkl. composer.json, composer.lock, config/)
git add -A
git commit -m "[TASK-NNN] Installera modul X"
git push

# 5. Deploy till prod
cd /home/tritonled/htdocs/tritonled.se
composer install
vendor/bin/drush en [modul] -y
vendor/bin/drush cim --partial -y
vendor/bin/drush cr
```

---

## Vanliga misstag och hur du undviker dem

| Misstag | Konsekvens | Undvik med |
|---------|------------|------------|
| `cex` direkt efter prod-DB-import | Raderar lokal config | `cim --partial` först |
| Skapa fält direkt på prod | Config finns aldrig i Git | Alltid lokalt |
| `cim` (utan `--partial`) på prod | Raderar prod-config som inte finns i YAML | Alltid `--partial` |
| Glömma `git pull` innan `cim` på prod | Gammal config importeras | Pull alltid först |
| Layout Builder-layout skapad lokalt | Syns inte på prod | Skapa direkt på prod |

---

## Snabbreferens: Rätt miljö för rätt uppgift

```
Ska du bygga ett nytt fält?          → LOKALT
Ska du installera en modul?          → LOKALT
Ska du skapa en view?                → LOKALT
Ska du lägga till en produkt?        → PROD (eller lokalt för test)
Ska du ladda upp en bild?            → PROD
Ska du sätta Layout Builder-layout?  → PROD (eller lokalt, bygg om på prod)
Ska du översätta ett fält?           → PROD
```

---

**Version**: 1.0
**Skapad**: 2026-04-21
**Författare**: Stefan + Claude
