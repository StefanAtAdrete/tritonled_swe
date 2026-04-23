# TASK-032 — Product Card (display-component)

## FDT-klass: display-component + data-model
## Status: ✅ KLAR — Committat och pushad

---

## Steg 0 — SPEC ✅ KLAR

**Mockup:** `/docs/tasks/assets/task-032-mockup.html`

**Slutresultat:**
- Produktbild med miljöbadge (övre höger)
- Titel — klickbar, ingen underline
- 3 tekniska feature-punkter via `field_short_description`
- Teknisk fot: Watt · Lumen
- `bg-light` card-body
- Hover-effekt (translateY + shadow)
- Hela kortet klickbart via CSS `::after` stretched-link

---

## Steg 1 — DATA ✅ KLAR

- `field_short_description` på alla 10 bundles ✅
- `field_installation_environment` (taxonomy) på alla 10 bundles ✅
- `field_watt_range` + `field_lumen_range` på alla 10 bundles ✅
- Taxonomi `installation_environment`: Allround, Utomhus, Lager & industri, Ex-miljö, Kontor, Gata & Area, Installation ✅
- Engelska översättningar på alla taxonomitermer ✅

---

## Steg 2 — CONTENT ✅ KLAR

Alla 26 produkter (15–40) har:
- `field_installation_environment` ifyllt (SV default + EN) ✅
- `field_watt_range` + `field_lumen_range` ifyllt ✅
- `field_short_description` (SV + EN) ifyllt ✅

Data-metod: JSON:API PATCH via browser (Claude in Chrome) — se FDT-skill för workflow.

---

## Steg 3 — DISPLAY ✅ KLAR

- View mode `commerce_product.card` + 10 entity view displays ✅
- Template `commerce-product--card.html.twig` ✅
- CSS: `web/themes/custom/tritonled_radix/css/components/cards.css` ✅

---

## Steg 4 — LAYOUT ✅ KLAR

- Views-block `tritonled_product_cards` på startsidan ✅
- Bootstrap Grid: `col-6 mb-4` / `col-sm-6 mb-4` / `col-md-3 mb-4` (vertikal radgutter) ✅

---

## Steg 5 — VERIFY ✅ KLAR

- ✅ Badge med rätt färg på SV + EN
- ✅ Features (3 punkter) på alla produkter
- ✅ Teknisk fot (Watt · Lumen)
- ✅ Klickbart kort med hover-effekt
- ✅ bg-light card-body
- ✅ Radavstånd mellan rader (mb-4 på col)
- ✅ Stefan godkänt

---

## Steg 6 — API ⏸ Parkerad

---

## Tekniska lärdomar

### Twig
- `{{ product.field_xxx }}` i Commerce — INTE `{{ content.field_xxx }}`
- `string_long` → formatter `basic_string`, inte `string`
- `|render|striptags|trim` HTML-encodar `&` → fix: `|replace({'&amp;': '&'})`
- `product.getUntranslated()` fungerar EJ i Twig sandbox
- `.entity.name.value` på entity reference fungerar EJ reliably i Twig sandbox
- `{{ url }}` är tom i Rendered Entity Views-kontext — använd CSS `::after` på title-länken istället för stretched-link anchor
- CSS stretched-link via `::after` på title-länken: `.triton-card-link a::after { content: ''; position: absolute; inset: 0; z-index: 1; }`
- **Tech debt:** Badge-färgvillkor bör till preprocess hook i `.theme`

### Flerspråkighet
- `field_installation_environment` är translatable → sätt på RÄTT translation
- SV-produkter: PATCH via `/jsonapi/` (default/SV)
- EN-baserade produkter (surge_protection m.fl.): PATCH via `/en/jsonapi/`
- Taxonomy-termer behöver EN-översättning för korrekt badge på EN
- Badge-villkor måste täcka BÅDA språkens term-namn

### Bootstrap / CSS
- `gy-3` på "Grid row custom class" i Views → fungerar EJ (mappar till wrapper, inte `.row`)
- Rätt approach: `mb-4` på col-klasserna i Views Bootstrap Grid
- Hover-effekt: `transform: translateY(-4px)` + `box-shadow` med `transition`
- `overflow-hidden` på `.card` klipper bildkanter snyggt

### JSON:API bulk-update
- Aktivera writes via UI, inte `drush config:set` (kräver cr, syncar ej alltid)
- Relationship PATCH: `relationships`-nyckeln, inte `attributes`
- Alltid stänga `read_only: true` direkt efter
- Se FDT-skill för komplett workflow
