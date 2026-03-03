# Task 012: Flerspråkighet & Product Tabs Bootstrap-styling

**Created**: 2026-03-03  
**Status**: In Progress  
**Last Updated**: 2026-03-03  
**Related Tasks**: TASK-011

---

## 1. DEFINE

### Mål
1. Aktivera och fylla i svenska/engelska översättningar för allt innehåll
2. Styla lb_tabs på produktsidan med Bootstrap-utseende

### Syfte
- SEO: Korrekt språkinnehåll per URL (/sv/, /en/)
- UX: Enhetligt Bootstrap-utseende på produktsidan
- Tillgänglighet: Korrekt språkmärkning av innehåll

### Acceptanskriterier
- [x] Alla produkter har både EN och SV
- [x] Alla customer cases har både EN och SV
- [x] Startsida har SV-översättning
- [x] Contact us-block översatt
- [x] Menyer översatta
- [x] Views-titlar översatta
- [ ] lb_tabs ser ut som Bootstrap tabs på produktsidan
- [ ] jQuery UI-initiering ersatt med Bootstrap tabs JS

**Godkänt av Stefan**: ✅ Godkänd

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`

### Vald lösning — Översättningar
**Approach**: Config + Drush php:eval  
- Content Translation aktiverat för alla entitetstyper
- locale + config_translation moduler aktiverade
- Svenska språkpaket importerade via `drush locale:update`
- Översättningar skapade via `addTranslation()` i Drush
- Views/block-titlar via `getLanguageConfigOverride()`

### Vald lösning — lb_tabs Bootstrap-styling
**Approach**: Template override + Custom JS (godkänt av Stefan)  
**Motivering**: jQuery UI initieras via `tabs.js` och skriver över Bootstrap-klasser.  
Template-override ensam räcker inte — jQuery UI JS måste ersättas.

**Lösning:**
1. Template override: `templates/layout/lb-tabs-tabs.html.twig` med Bootstrap-klasser
2. Custom JS i temat som initierar Bootstrap tabs istället för jQuery UI
3. Library override via `tritonled_radix.libraries.yml`

### Alternativ övervägda
1. **Enbart CSS-override** — jQuery UI JS skriver över klasser efter sidladdning → fungerar inte
2. **Byta modul** — ingen Bootstrap-native Layout Builder tabs-modul finns → ej aktuellt
3. **Layout Builder Styles** — kan inte lösa jQuery UI-konflikten → används som komplement för stilar

**Godkänt av Stefan**: ✅ Godkänd

---

## 3. IMPLEMENT

### Översättningar (Klart ✅)

1. **Content Translation aktiverat**
   - node: article, customer_cases, page
   - commerce_product_variation: default
   - media: audio, document, image, remote_video, video
   - block_content: basic
   - menu_link_content: menu_link_content

2. **Moduler aktiverade**
   - `locale` — systemsträngar och gränssnittsöversättningar
   - `config_translation` — config-entiteter (block-titlar etc.)

3. **Innehåll översatt**
   - Triton OPTI: EN uppdaterad (från PDF), SV skapad
   - Triton MAX: EN skapad (från SV)
   - Customer cases (4, 5, 6): SV skapade
   - Startsida (node 1): SV titel skapad
   - Contact us block: SV skapad ("Kontakta oss")
   - Main menu "Produkter": EN satt till "Products", SV "Produkter"

4. **Views/block-titlar**
   - `featured_products` → SV: "Utvalda produkter"
   - `customer_cases` → SV: "Kundcase"
   - `tritonled_radix_contactus` block label → SV: "Kontakta oss"

5. **Språkpaket**
   - `drush locale:update` — 7083 översättningar importerade

### lb_tabs Bootstrap (In Progress ⏳)

1. **Template override skapad** ✅
   - `templates/layout/lb-tabs-tabs.html.twig`
   - Bootstrap-klasser: `nav nav-tabs`, `nav-link`, `tab-content`, `tab-pane fade`
   - Problem: jQuery UI JS initieras efteråt och skriver över

2. **Custom JS — nästa steg** ⏳
   - Ersätt jQuery UI `tabs()` med Bootstrap tabs-initiering
   - Lägg till i `tritonled_radix.libraries.yml`

---

## 4. VERIFY

### Översättningar
- [x] /sv visar svenska innehåll
- [x] /en visar engelska innehåll  
- [x] Menyval: EN "Products", SV "Produkter"
- [x] Views: "Utvalda produkter" / "Featured Products"
- [x] Footer: "Kontakta oss" / "Contact us"

### lb_tabs
- [ ] Bootstrap tab-utseende på /sv/product/8
- [ ] Klick på tab byter innehåll korrekt
- [ ] AJAX fungerar fortfarande vid variantbyte

---

## 5. COMPLETION

### Status: 🔄 In Progress

### Lärdomar
- `lb_tabs` använder jQuery UI som initieras via JS — template-override ensam räcker inte
- Block-titlar översätts via `getLanguageConfigOverride()` på `block.block.[id]`
- Views-titlar översätts via `getLanguageConfigOverride()` på `views.view.[id]`
- `locale:update` hämtar alla svenska systemöversättningar automatiskt
- `menu_link_content` måste ha content_translation aktiverat för att kunna översättas

### Nästa steg
- Implementera custom JS för Bootstrap tabs-initiering
- Skapa Layout Builder Styles-grupp för product tabs
