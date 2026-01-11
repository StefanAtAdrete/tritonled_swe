# Front Page Visual Testing - Resultat & Fixar

**Datum:** 2025-01-11  
**Browser:** Chromium via Puppeteer  
**Resolution:** 1920x1080  
**URL:** http://tritonled.ddev.site/

---

## 📸 SCREENSHOTS TAGNA

1. `tritonled-frontpage-current.png` - Top viewport
2. `tritonled-frontpage-middle.png` - Middle scroll
3. `tritonled-frontpage-bottom.png` - Lower content
4. `tritonled-frontpage-footer.png` - Bottom with errors

---

## ✅ VAD SOM FUNGERAR

### 1. CTA Section (Section 5)
**Status:** ✅ PERFEKT  
**Visuellt:**
- Blue background (Bootstrap `bg-primary`)
- White text
- "Ready to Upgrade Your Lighting?" rubrik synlig
- "Request Quote" button synlig och centered
- Full-width section

**No changes needed.**

---

### 2. Browse by Application (Section 2)
**Status:** ⚠️ FUNGERAR MEN SAKNAR STYLING  
**Visuellt:**
- 6 taxonomy links syns:
  - Cold Storage
  - Hazardous Locations
  - Manufacturing
  - Parking Garages
  - Sports Facilities
  - Warehousing
- Klickbara länkar (blå text)

**Problem:**
- Ingen pill/badge styling
- Länkar är vertikala istället för grid
- View-konfigurerade klasser appliceras inte

**Fix needed:** Bootstrap pill styling saknas, row/col classes fungerar inte

---

## ❌ STORA PROBLEM IDENTIFIERADE

### 1. PHP ERRORS - KRITISKT
**Errors synliga i rosa box längst ned:**
```
Warning: Undefined array key "empty_zero" in ...
Warning: Undefined array key "element_default_classes" in ...
```

**Orsak:** Views field configuration har felaktiga keys.

**Views påverkade:**
- browse_by_application
- featured_products  
- performance_features

**Root cause:**
- Layout Builder STÖDJER INTE `additional.section_classes` syntax
- Views field config har keys som inte existerar i Drupal 11

**Fix:**
1. Ta bort `additional.section_classes` från Layout Builder config
2. Rensa felaktiga keys från Views field config
3. Använd CSS classes istället

---

### 2. Hero Carousel - SAKNAS HELT
**Status:** ❌ SYNS INTE  
**Förväntat:** Full-width carousel med produktbilder/videos  
**Resultat:** Ingenting renderas där hero ska vara

**Möjliga orsaker:**
- Layout Builder section inte korrekt konfigurerad
- Views block `hero_media-block_1` finns inte
- Template fel
- Block inte placerad

**Debug needed:**
```bash
# Kolla om hero view finns
ddev drush views:list | grep hero

# Kolla Layout Builder data
ddev drush sqlq "SELECT * FROM node__layout_builder__layout WHERE entity_id = 10"
```

**Fix plan:**
1. Verifiera hero view existerar
2. Kontrollera Layout Builder sections
3. Testa hero view separat (/admin/structure/views/view/hero_media)

---

### 3. Performance Features - FEL INNEHÅLL
**Status:** ❌ VISAR LOREM IPSUM  
**Förväntat:** 
- High Efficiency LED Technology
- Extended Lifespan Technology  
- Industrial-Grade Construction

**Resultat:** Fulltext Lorem Ipsum paragraf istället för summaries

**Orsak:** View visar `body.value` (fulltext) istället för `body.summary`

**Fix:**
```yaml
# I views.view.performance_features.yml
body:
  type: text_summary_or_trimmed
  settings:
    trim_length: 200  # <-- Detta ignoreras, visar full body
```

**Lösning:** Ändra formatter till `text_trimmed` ELLER fixa field display

---

### 4. Featured Products - FEL RUBRIK
**Status:** ⚠️ VISAR FEL TITEL  
**Förväntat:** "Featured Products"  
**Resultat:** "Top Rated High Bays"

**Orsak:** View title inte overridad i block placement

**Fix:** Update block configuration eller view title

---

### 5. Login Block - SYNLIG PÅ FRONT PAGE
**Status:** ❌ BORDE INTE SYNAS  
**Resultat:** User login form högst upp på sidan

**Orsak:** Block placerad i header/sidebar region som visas på alla sidor

**Fix:** 
- Konfigurera block visibility (exclude front page)
- Eller ta bort från region helt

---

### 6. Browse Pills - INGEN GRID LAYOUT
**Status:** ❌ VERTIKAL LISTA  
**Förväntat:** 6 pills i 2 rows (3 per row på desktop)  
**Resultat:** Vertical list med länkar

**Orsak:** View row classes `col-lg-2 col-md-4 col-6` appliceras inte

**Views config problem:**
```yaml
style:
  type: default
  options:
    row_class: 'col-lg-2 col-md-4 col-6 mb-3 text-center'  # <-- Ignoreras
```

**Fix:** 
1. Wrapper `<div class="row">` fungerar (i header/footer)
2. Men `row_class` på individual items fungerar INTE
3. Behöver custom template ELLER Display Suite

---

## 🔧 FIX PRIORITY

### P0 - KRITISKA (Blockar allt)
1. **PHP Errors** - Ta bort felaktiga array keys från Views
2. **Hero Carousel** - Få den att visas

### P1 - VIKTIGA (Förstör UX)
3. **Performance Features** - Rätt innehåll (summaries)
4. **Browse Pills** - Grid layout + badge styling
5. **Login block** - Dölj på front page

### P2 - NICE TO HAVE
6. **Featured Products title** - Rätt rubrik

---

## 📋 TEKNISKA DETALJER

### Layout Builder Config Problem

**Felaktig syntax (används i config):**
```yaml
components:
  uuid-here:
    additional:
      section_classes: 'container py-5'  # <-- STÖDS INTE
```

**Detta fungerar INTE i Drupal 11 Layout Builder.**

**Alternativ:**
1. **Layout Builder Styles module** - Tillåter class injection via UI
2. **Custom Layout Plugin** - Skapa egen layout med klasser
3. **CSS via theme** - Target sections med CSS selectors
4. **Twig template override** - Override layout templates

---

### Views Field Config Problem

**Felaktiga keys i YAML:**
```yaml
fields:
  name:
    element_default_classes: false  # <-- Undefined key warning
    convert_spaces: false            # <-- OK
    exclude: false                   # <-- OK
```

**Fix:** Ta bort `element_default_classes` och andra undefined keys

---

## 🎯 NÄSTA STEG (i ordning)

### Steg 1: Rensa PHP Errors
```bash
# Uppdatera Views configs
# Ta bort: element_default_classes, empty_zero
# Från: browse_by_application, featured_products, performance_features
```

### Steg 2: Fixa Hero Carousel
```bash
# Verifiera view finns
ddev drush views:list | grep hero

# Kolla Layout Builder sections
ddev drush config:get core.entity_view_display.node.page.default

# Testa hero view direkt
# Besök: /admin/structure/views/view/hero_media
```

### Steg 3: Fixa Performance Features Content
```bash
# Ändra view field formatter från text_summary_or_trimmed till specifik summary
# Eller skapa custom field template
```

### Steg 4: Bootstrap Grid för Pills
**Alternativ A:** Display Suite (rekommenderat)
**Alternativ B:** Custom Views template
**Alternativ C:** Layout Builder Styles module

### Steg 5: Dölj Login Block
```bash
# Block visibility settings
ddev drush config:get block.block.tritonled_account_menu
# Eller via UI: /admin/structure/block
```

---

## 🧪 TEST KOMMANDO (för framtida testing)

```bash
# Ta screenshot via Puppeteer
ddev exec "node -e \"
const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.setViewport({width: 1920, height: 1080});
  await page.goto('http://tritonled.ddev.site/');
  await page.screenshot({path: '/var/www/html/test-screenshot.png'});
  await browser.close();
})();
\""
```

---

## 📊 SUMMARY METRICS

**Working:** 1/5 sections (20%)  
**Partial:** 1/5 sections (20%)  
**Broken:** 3/5 sections (60%)  
**PHP Errors:** ~14 warnings visible  
**Critical blockers:** 2 (PHP errors, missing hero)

---

## 💾 BACKUP INNAN FIX

```bash
# Exportera nuvarande config
ddev drush config:export -y

# Backup views
cp config/sync/views.view.* /tmp/views-backup/

# Backup Layout Builder
cp config/sync/core.entity_view_display.node.page.default.yml /tmp/
```

---

**Status:** 🔴 Requires immediate fixes  
**Next:** Fix P0 items (PHP errors + Hero carousel)  
**Tokens remaining:** ~73,000 (gott om för fixes)
