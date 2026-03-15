# TASK-016: Navigation & Mobil meny

## Status: COMPLETED
**Senast uppdaterad:** 2026-03-15

---

## Mål
Snygga till navbar och mobilmeny — konsekvent design, rätt Bootstrap-klasser, bra UX på mobil.

---

## Genomfört (alla sessioner)

### Topbar & CTA-knappar
- "Få offert" (Cart-block) — visas på `product/*`
- "Kontakta oss" — visas på alla andra sidor
- Synlighetsvillkor matchar mot `currentPath` UTAN språkprefix och UTAN inledande `/`
- Rätt mönster: `product/*` (inte `/sv/product/*`)

### Offcanvas mobilmeny — collapse-struktur (denna session)
- Ny region `navbar_offcanvas` tillagd i `tritonled_radix.info.yml`
- Separat meny-block `tritonled_radix_mainnavigation` placerat i `navbar_offcanvas`
- `page.html.twig` + `page--front.html.twig` uppdaterade — offcanvas-body använder `navbar_offcanvas` istället för `navbar_left`
- Template: `menu--main--tritonled-radix-mainnavigation.html.twig` — Bootstrap Collapse-struktur
- `hook_theme_suggestions_menu_alter` + `preprocess_block` i `tritonled_radix.theme`
- CSS: `css/components/offcanvas-menu.css` — chevron-rotation, knapp-reset
- Förälderlänk med undermeny = `<button>` med `justify-content-between` (ingen sidladdning)

### Cart z-index
- `.cart-block--contents { z-index: 999 !important }` i `style.css`
- Commerce's egen CSS sätter z-index: 300 — vår override krävs

### Custom block content → Views (deploy-fix)
- **Problem:** `block_content`-entiteter är innehåll, inte config — följer inte med i `cim`
- **Lösning:** Bytt ut mot Views med Global: Custom text-fält
- "Kontakta oss"-knapp → View `tritonled_contact_button` (block display)
- "Cookie-inställningar" → View `tritonled_cookie_settings` (block display)
- Views är ren config — exporteras med `cex`, importeras med `cim` ✅
- Views Custom text kan översättas via Drupals översättningssystem ✅

### Klaro cookie-länk
- Views Custom text strippar `onclick`-attribut av säkerhetsskäl
- Lösning: CSS-klass `klaro-open` på länken + JS-behavior i `global.js`
- `Drupal.behaviors.tritonledKlaroOpen` hanterar klick och anropar `klaro.show()`

---

## Lärdomar

### Custom block content följer INTE med i deploy
- Block-*placeringar* (config) följer med via `cim`
- Block-*innehåll* (block_content-entiteter) är databas-innehåll — måste skapas manuellt på prod
- **Lösning:** Använd Views med Custom text för statiska block som behöver deploy-säkerhet

### Views kan översättas
- Views Custom text-fält kan översättas via Drupals inbyggda översättningssystem
- Menyer är alternativet om redaktörer behöver redigera via UI

### JS-cache på prod
- Efter deploy med ny JS — hård reload (Ctrl+Shift+R) krävs i browsern för att se ny kod
- `drush cr` rensar server-cache men inte browser-cache

### hook_theme_suggestions_menu_alter
- Lägger till block-ID som template-suggestion för menyer
- Kräver `preprocess_block` som skickar `block_id` till menu-elementets attributes
- Ger `menu--main--[block-id].html.twig` per block-instans

---

## Tekniska filer

### Templates
- `web/themes/custom/tritonled_radix/templates/page/page.html.twig`
- `web/themes/custom/tritonled_radix/templates/page/page--front.html.twig`
- `web/themes/custom/tritonled_radix/templates/navigation/menu--main--tritonled-radix-mainnavigation.html.twig`

### CSS
- `web/themes/custom/tritonled_radix/css/style.css` — cart z-index
- `web/themes/custom/tritonled_radix/css/components/offcanvas-menu.css` — chevron, knapp

### JS
- `web/themes/custom/tritonled_radix/js/global.js` — Klaro behavior

### Theme
- `web/themes/custom/tritonled_radix/tritonled_radix.theme` — hook_theme_suggestions_menu_alter, preprocess_block
- `web/themes/custom/tritonled_radix/tritonled_radix.info.yml` — region navbar_offcanvas

### Block-ID:n
- Desktop-meny: `tritonled_radix_main_menu` (navbar_left)
- Offcanvas-meny: `tritonled_radix_mainnavigation` (navbar_offcanvas)
- Cart/offert-block: `tritonled_radix_cart`
- Kontakta oss (View): `tritonled_radix_views_block__tritonled_contact_button_block_1`
- Cookie-inställningar (View): `tritonled_radix_views_block__tritonled_cookie_s...`
