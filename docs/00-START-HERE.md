# TritonLED - Sessionsstart Guide

⚠️ **CLAUDE: LÄS DENNA FIL FÖRST VID VARJE SESSION**

**DÄREFTER**: Läs `/docs/CURRENT-TASK.md` (om den finns) för pågående uppgift

---

## 🔄 Sessionsstruktur (ALLTID följa)

### DEL 1 — START
Claude gör vid varje sessionsstart:
1. Läser denna fil (`00-START-HERE.md`)
2. Läser `CURRENT-TASK.md`
3. Presenterar: var vi är, öppna tasks, förslag på vad vi tar tag i

Stefan kontrollerar:
```bash
ddev start
ddev drush status
```

### DEL 2 — CHECKPOINT (mitt i session)
**Claude påminner aktivt** när något av följande inträffar:
- ✅ En task markeras som klar
- ⏱️ Lång session utan naturligt avbrott
- 🔀 Vi byter task/inriktning

Claude säger då: *"✅ [TASK-NNN] klar. Dags för checkpoint — kör vi det nu?"*

Checkpoint-steg:
```bash
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Checkpoint: vad som är klart"
```
- Uppdatera `CURRENT-TASK.md` (status, vad återstår)
- Stefan tar Backup & Migrate snapshot

### DEL 3 — SESSIONSSLUT
```bash
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Session slut: sammanfattning"
git push origin main
```
- Uppdatera `CURRENT-TASK.md` + `00-START-HERE.md` (nya beslut)
- Stefan tar Backup & Migrate snapshot (om inte nyligen gjort)
- Deploy till prod om redo (se deploy-flöde nedan)

**Detaljerad SOP**: `/docs/04-workflows/session-sop.md`

---

## 🚨 KRITISKT: FILSYSTEM-REGLER (BRYTS ALDRIG!)

### Claude har tillgång till 2 datorer:

**1. STEFANS MAC (Drupal-projektet)** ← **ANVÄND ALLTID FÖR PROJEKTET**
- Sökväg: `/Users/steffes/Projekt/tritonled/`
- Verktyg: `Filesystem:*` (Capital F)

**2. CLAUDES DATOR (temporära filer)**
- Sökväg: `/home/claude/`
- Verktyg: `bash_tool`, `create_file`

### ✅ RÄTT för Drupal-projektet:
```
Filesystem:read_text_file     → Läsa filer
Filesystem:write_file         → Skapa/uppdatera filer
Filesystem:list_directory     → Lista kataloger
Filesystem:search_files       → Söka filer
Filesystem:move_file          → Flytta/byta namn
Filesystem:create_directory   → Skapa kataloger
```

### ❌ FEL för Drupal-projektet:
```
bash_tool                     → Kör BARA på Claudes dator
create_file                   → Skapar på Claudes dator
ls, find, cat kommandon       → Fungerar INTE på Stefans Mac
```

### 🔧 För DDEV/Drush kommandon:
```
✅ GE Stefan kommandot att köra själv
❌ ALDRIG försök köra ddev/drush själv
```

### Exempel:
```bash
# ❌ FEL (försöker på Claudes dator):
bash_tool: ls /Users/steffes/Projekt/tritonled/web/themes

# ✅ RÄTT (använder Stefans Mac):
Filesystem:list_directory
path: /Users/steffes/Projekt/tritonled/web/themes
```

**OM DU GLÖMMER DETTA = PROJEKTET FUNGERAR INTE!**

## 📋 Snabbfakta

- **Projekt**: TritonLED E-commerce (LED luminaires)
- **CMS**: Drupal 11.3.5
- **Miljö**: DDEV lokal utveckling
- **Theme**: Radix (Bootstrap 5.3)
- **Layout**: Layout Builder + Bootstrap Layout Builder
- **Commerce**: Drupal Commerce (quote-baserat system)
- **Målgrupp**: Professionella köpare (installatörer, elektriker, projektledare)

## 🧩 Huvuduppgifter delas ALLTID upp i sub-tasks

**Innan du börjar med någon uppgift – identifiera sub-tasks och deras ordning.**

En frontend-sektion i Drupal är aldrig bara en uppgift. Den består av lager som
måste byggas i rätt ordning. Verifiera verktyg och regler per sub-task INNAN implementation.

### Standardordning för frontend-sektioner:

```
1. Innehåll       → Finns rätt content type / media type? (produkter, noder, media)
2. Image styles   → Rätt bildformat per breakpoint (MÅSTE finnas innan view modes)
3. View modes     → Hur renderas innehållet i sin kontext? (hero, card, teaser...)
4. Views          → Samlar och strukturerar (block/page) med contrib format-plugins
5. Layout Builder → Placerar blocket på sidan
6. Styling        → Bootstrap klasser FÖRST, sedan minimal CSS (kräver godkännande)
7. SDC/Template   → Sista utväg, kräver EXPLICIT godkännande
```

### Viktigt om innehåll:
- **Använd alltid befintliga content types/produkter** innan du föreslår nya
- Produkter (Commerce) finns redan – använd dem för produktrelaterade sektioner
- Skapa nytt content type ENDAST om befintligt verkligen inte passar

### Vad kräver godkännande?

| Åtgärd | Kräver godkännande? |
|--------|---------------------|
| Config via admin UI | NEJ |
| Image styles, view modes, views | NEJ |
| Bootstrap klasser | NEJ |
| Preprocess hook | JA |
| Custom CSS-fil | JA |
| Template (.html.twig) | JA – explicit |
| SDC-komponent | JA – explicit |
| Custom modul | JA – explicit |

### Research ALLTID innan nya fält skapas:

```bash
# Lista befintliga fält på en variation
ddev drush php:eval "
\$fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('commerce_product_variation', 'default');
foreach(\$fields as \$name => \$def) {
  if (str_starts_with(\$name, 'field_')) echo \$name . ' (' . \$def->getType() . ')' . PHP_EOL;
}
"
```

1. Finns liknande fält redan? → Använd dem
2. Räcker ett boolean-fält? → Använd det
3. Taxonomy motiveras endast om: logotyper, hierarki, Views-filtrering

### Commerce-undantag:
- Drupal Commerce kräver templates som **inte stör AJAX**
- Templates för produktsidor får ALDRIG blockera variation field injection
- Se: `03-solutions/commerce-ajax-solution.md`

**Läs mer**: `04-workflows/task-decomposition.md`

---

## 🎯 Task-Driven Workflow (ALLTID)

**Vid ny uppgift:**
1. ✅ Skapa `/docs/tasks/task-NNN-beskrivning.md` från TASK-TEMPLATE.md
2. ✅ Fyll i **DEFINE** (mål, syfte, acceptanskriterier) → Vänta på Stefan OK
3. ✅ Fyll i **PLAN** (beslutsträd, lösning, motivering) → Vänta på Stefan OK
4. ✅ **IMPLEMENT** steg-för-steg med git commits `[TASK-NNN] Message`
5. ✅ **VERIFY** mot acceptanskriterier
6. ✅ Om FAIL → Iteration 2 i samma task-fil
7. ✅ Om PASS → Dokumentera i `/docs/03-solutions/` och markera task som Completed

**Varje git commit:**
```bash
git commit -m "[TASK-NNN] Beskrivning av ändring"
git commit -m "[TASK-NNN-01] Sub-task beskrivning"
```

## 🚫 Arbetsregler - ALDRIG

❌ **ALDRIG koda innan godkänt**
❌ **ALDRIG skapa templates utan explicit tillstånd**  
❌ **ALDRIG hoppa över beslutsträdet**
❌ **ALDRIG gissa - fråga om osäker**

## ✅ Arbetsregler - ALLTID

✅ **ALLTID** config och moduler först
✅ **ALLTID** contrib-moduler före custom kod
✅ **ALLTID** förklara VARFÖR, inte bara HUR
✅ **ALLTID** följ `/docs/DRUPAL-DECISION-TREE.md`
✅ **ALLTID** Layout Builder för sidlayouter

## 🌍 Språk

- **Frontend**: Svenska (produktbeskrivningar, UI)
- **Admin/Backend**: Engelska (Drupal standard)
- **Kod/kommentarer**: Engelska (best practice)
- **Dokumentation**: Svenska (denna) + Engelska (kod)

## 🔧 Tech Stack Detaljer

### Tema & Styling
- **Base theme**: Radix
- **CSS Framework**: Bootstrap 5.3 (via CDN)
- **Layout**: Layout Builder + Bootstrap Layout Builder module
- **Custom CSS**: Minimalt - endast i `css/components/` när absolut nödvändigt

### Commerce
- **System**: Quote-baserat (EJ direktköp)
- **Produkter**: LED luminaires med varianter
- **Attribut**: Watt, CCT (färgtemperatur), CRI, IP-rating, monteringstyp
- **Import**: CSV in (Excel → export), JSON ut (partner-API)
- **Priser**: Lagras i databasen men renderas ALDRIG på frontend
  - Ej gömda via CSS — bokstavligen ej i någon frontend view mode
  - Exponeras via JSON-export för partners med API-access

### Verktyg
- **MCP Tools**: Direkt Drupal entity-manipulation
- **DDEV**: Lokal utveckling
- **Git**: Versionskontroll
- **Drush**: CLI administration

## 🚀 Deploy till Produktion

### Förutsättningar
- Produktionsservern kör på Hostinger VPS: `/home/tritonled/htdocs/tritonled.se`
- **Alltid** använd `vendor/bin/drush` på produktion — aldrig bara `drush` (fel projekt körs annars)
- `settings.php` är INTE i git — den måste underhållas manuellt på servern
- `settings.php` innehåller: `$databases`, `$settings['hash_salt']`, `config_sync_directory`, `file_private_path`

### Deploy-flöde (ALLTID följa denna ordning)

```bash
# 1. LOKALT — exportera config och pusha
ddev drush cex -y
git add -A
git commit -m "[TASK-NNN] Beskrivning"
git push origin main

# 2. PÅ PRODUKTIONSSERVERN
cd /home/tritonled/htdocs/tritonled.se
git pull
vendor/bin/drush cim --partial -y
vendor/bin/drush cr
```

### ⚠️ Kritiska regler för produktion
- ❌ ALDRIG `git reset --hard` utan att säkerhetskopiera `settings.php` först
- ❌ ALDRIG `git stash pop` utan att veta vad stashen innehåller
- ✅ Om `git pull` misslyckas med divergent branches: kontakta Stefan innan åtgärd
- ✅ `settings.php` ska alltid innehålla korrekt `$databases` och `hash_salt`
- ✅ Verifiera alltid med `vendor/bin/drush status` att rätt projekt körs

### Block content på produktion
- **Custom block content** (skapade via Content → Block content) följer INTE med i config/sync
- De är innehållsentiteter (som noder) och lagras i databasen
- Vid deploy till ny miljö måste de återskapas manuellt via `vendor/bin/drush php:eval`
- UUID i `block.block.*.yml` måste matcha block content-entitetens UUID på servern
- Om UUID inte matchar: uppdatera via `drush php:eval` med `configFactory()->getEditable()`

### Systemblock vs Custom block content
| Typ | Exempel | Följer med i config? |
|-----|---------|---------------------|
| Systemblock | Cart, Language switcher, Menu | ✅ Ja |
| Custom block content | "Kontakta oss"-knapp | ❌ Nej — måste återskapas |

---

## 📊 Senaste Viktiga Beslut

### Session SOP (2026-03-29)
- ✅ Sessionsstruktur dokumenterad i `04-workflows/session-sop.md`
- ✅ Tre delar: Start, Checkpoint (mitt i), Slut
- ✅ Claude påminner aktivt vid avklarad task eller byte av inriktning
- ✅ Checkpoint inkluderar: cex, git commit, CURRENT-TASK.md, Backup & Migrate

### Konfigurator Bootstrap dropdowns (2026-03-27)
- ✅ Native `<select>` ersatt med Bootstrap 5 custom dropdowns i `configurator.js`
- ✅ Bootstrap/Popper.js hanterar positionering — öppnar alltid nedåt på mobil
- ✅ Ingen backend-ändring — POST-data och CartController oförändrade
- ✅ `updateVisibility()`, `autoSelectFirst()`, `clearSelectionsAfter()` uppdaterade för dropdown-struktur
- ❌ Native `<select>` på mobil kan INTE styras med CSS — öppnar uppåt/nedåt baserat på viewport-position
- Se: `tasks/task-023-konfigurator-mobiloptimering.md`

### Konfigurator media-entiteter — namnkonvention (2026-03-27)
- ✅ Media-namn MÅSTE matcha `{imagePrefix}-{endcap_code}` exakt (t.ex. `TM-C`, `TME-E`, `TMED-V`)
- ✅ Om flera produktserier delar bilder: skapa separata media-entiteter med rätt prefix men peka på samma FID
- ✅ MAX-PRO (TMP) och MAX-ED (TMED) återanvänder FIDs från TM/TME men har egna MIDs
- ✅ `{imagePrefix}-default` = fallback-bild när inget endcap-val matchar
- ❌ Döp INTE om befintliga media-entiteter — skapa nya med rätt namn istället (Approach B)
- Media-MIDs per produkt: se CURRENT-TASK.md

### Konfigurator bildväxling — separata block (2026-03-20)
- ✅ `ConfiguratorImageBlock` = separat block plugin för bilden — placeras fritt i Layout Builder
- ✅ `ConfiguratorBlock` = bara konfigurator-UI (dropdowns, SKU, knapp)
- ✅ `#prefix`/`#suffix` fungerar INTE på media-render-arrayer — använd `'#type' => 'container'` som wrapper
- ✅ View mode `configurator_image` på media.image: alla fält utom `Image` måste sättas till Disabled
- ✅ `imagePictures` byggs server-side i preprocess, JS byter bara `src`/`srcset` på befintlig `<img>`
- Se: `03-solutions/configurator-image-switching.md`

### Feeds import-ordning och store-koppling (2026-03-06)
- ✅ Products-feed ALLTID FÖRST → sätter store-koppling på produkten
- ✅ Variations-feed SEDAN → importerar varianter
- ❌ Om produkten skapas manuellt utan products-feed → saknar store-koppling → 500-fel vid AJAX
- ✅ `tritonled_compat` FeedsImportSubscriber rensar feeds_item automatiskt efter varje import
- ✅ Feeds-tabeller: `commerce_product_variation__feeds_item` och `commerce_product__feeds_item`
- ✅ Products-CSV: en rad på engelska → svenska via Drupal Translate-UI
- Se: `03-solutions/feeds-import-ordning.md`

### Feeds Item AJAX Bug (2026-02-21)
- ⚠️ `feeds_item` på varianter orsakar 500-fel vid Media Library AJAX
- ✅ Fix: Rensa `feeds_item` på alla varianter efter import
- ⚠️ Måste upprepas efter varje CSV-import
- Se: `03-solutions/feeds-item-ajax-bug.md`

### File-fält vs Media-fält (2026-02-21)
- ❌ ALDRIG File-fält på entiteter som har Media Library-widgets
- ✅ Använd alltid Media-entiteter (image, document, video)
- File + Media Library kolliderar vid AJAX-validering

### Commerce AJAX (2025-01-08)
- ✅ Använd Event Subscribers för custom beteende
- ❌ Använd INTE custom product templates (förstör AJAX)
- ✅ Layout Builder för layout
- Se: `03-solutions/commerce-ajax-solution.md`

### Bootstrap Layout Builder NULL-attribut bug (2026-02-26)
- BLB sparar ibland `NULL` for `container_wrapper_attributes` och `section_attributes`
- Orsakar `Warning: foreach() argument must be of type array|object, string given`
- Fix: PHP-script som itererar sektioner och sätter `[]` för NULL-värden
- Se: `tasks/task-006-footer-layout.md`

### Language config incident (2026-03-04)
- ❌ ALDRIG `ddev drush config:delete language.negotiation` — bryter sajten omedelbart
- ❌ ALDRIG ändra language detection utan att ta snapshot först
- ✅ Ta alltid `ddev snapshot` innan språkinställningar ändras
- ✅ Vid trasig language.negotiation: återskapa via `php:eval` med korrekt struktur
- ✅ Vid blockerad `cim`: rensa config-objekt som beror på avinstallerade moduler via `php:eval` + `->delete()`
- ✅ `cim --partial` fungerar bättre än full `cim` vid partiella problem
- ⚠️ navigation-modulen (Drupal core experimental) är avinstallerad
- ⚠️ Commerce translation-routes kräver explicit permission

### Splide thumbnail overflow-fix (2026-03-01)
- ❌ `border` på `.splide__slide` påverkar Splide's layoutberäkning
- ✅ Använd `outline` + `outline-offset: -2px` för aktiv-markering
- ✅ `.splide--nav .splide__track { overflow: visible !important; }` fixar klippt första tumme
- ✅ `trimSpace: move` i product_nav optionset

### lb_tabs + Layout Builder (2026-03-01)
- ✅ `lb_tabs` skapar tabs-layout direkt i Layout Builder
- ✅ Pseudo-fält placeras som block i respektive tab
- ✅ AJAX fungerar utan ändringar
- ✅ Template reducerad till minimal wrapper: `<article>{{ product }}</article>`
- ❌ Blanda INTE templatens grid med Layout Builder

### commerce_variation_blocks AJAX (2026-02-28)
- ✅ Commerce använder **Events**, inte hooks, för AJAX-tillägg vid variantbyte
- ✅ Rätt event: `ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE` → `EventSubscriber`
- ❌ `hook_commerce_product_variation_field_injection` existerar INTE
- Se: `03-solutions/verify-before-implement.md`

### Produktsida attribut & styling (2026-02-27)
- ✅ Attribut-väljare bytta från dropdowns till radiobuttons
- ✅ Radiobuttons stylade som pill-knappar via CSS
- ✅ Bootstrap Icons CDN lagt till i `tritonled_radix.libraries.yml`

### Topbar + Navbar regioner (2026-02-27)
- ✅ Lade till `topbar_left`, `topbar_right`, `navbar_left_2`, `navbar_left_3` som regioner
- ✅ SDC-komponenter från Radix kan INTE overridas från child theme
- ✅ Rätt lösning: Bygg navbar direkt i page-templates

### Responsive Images (2025-01-08)
- ✅ 4:3 aspect ratio över ALLA breakpoints
- ✅ Focal Point module
- Se: `03-solutions/responsive-images.md`

### Layout Approach
- ✅ Layout Builder för alla sidlayouter
- ✅ Bootstrap Layout Builder för grids
- ✅ Field formatters + view modes för field display
- ❌ INTE Paragraphs (överdrivet)

## 🔍 När du är osäker

### 1. Sök i befintlig dokumentation
```
Läs: /docs/01-decision-trees/[relevant-tree].md
Kolla: /docs/03-solutions/ för tidigare lösningar
```

### 2. Kolla i projektet
```
view /Users/steffes/Projekt/tritonled/docs/[fil]
```

### 3. Fråga Stefan
❓ **Fråga ALLTID innan du gissar**

## 📝 Arbetsflöde - Steg för steg

### Vid ny uppgift:

1. **Förstå**: Läs uppgiften och be om förtydliganden
2. **Kolla docs**: Finns lösning i `03-solutions/`?
3. **Välj beslutsträd**
4. **Presentera plan**
5. **Vänta på OK**
6. **Implementera**
7. **Testa**
8. **Dokumentera**

## 🎨 Design → Implementation

**KRITISK ORDNING (följ ALLTID):**
1. **Bootstrap klasser FÖRST** - 80% kan lösas här
2. **Core Drupal functions** - Responsive images, view modes, image styles
3. **Kan core lösa det?** - Layout Builder, Views, field formatters
4. **Views + minimal templates** - Endast om nödvändigt
5. **SDC** - Sista utväg (nästan aldrig behövs)

## 🧪 Testing

**Efter varje ändring:**
```bash
ddev drush cr
ddev logs
ddev drush watchdog:show --severity=Error
```

## 📚 Fil-struktur

```
/docs/
├── 00-START-HERE.md          ← Du är här
├── CURRENT-TASK.md           ← Läs efter 00-START-HERE
├── DRUPAL-DECISION-TREE.md   ← Huvudbeslutsträd
├── 01-decision-trees/
├── 02-standards/
├── 03-solutions/
├── 04-workflows/
└── tasks/
```

## ⚠️ KRITISKT: Config export INNAN import

```bash
1. ddev drush cex -y       ← Exportera FÖRST
2. Lägg till/ändra YAML
3. ddev drush cim -y       ← Importera
4. ddev drush cr
```

## 🚀 Quick Commands

```bash
ddev drush cr
ddev drush cex -y
ddev drush cim -y
ddev composer require drupal/[module]
ddev drush en [module] -y
ddev logs -f
ddev snapshot
ddev snapshot restore [name]
```

## 🎓 Kom ihåg

1. **Config > Modules > Themes > Custom Code**
2. **Layout Builder för layouts**
3. **Bootstrap för styling**
4. **Field formatters + view modes för fields**
5. **Fråga innan koda**

---

**Version**: 2.5
**Skapad**: 2025-01-10
**Uppdaterad**: 2026-03-29 - Sessionsstruktur (SOP) tillagd
**Författare**: Stefan + Claude
