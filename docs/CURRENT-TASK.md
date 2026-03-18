# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 2 klar, SESSION 3 nästa
**Senast uppdaterad**: 2026-03-18

---

## Var vi är

### SESSION 1 ✅ Klar (2026-03-18)

- ✅ 12 Commerce-produkter verifierade med `field_configurator_schema` ifyllt
- ✅ `tritonled_configurator` modul skapad och aktiverad
- ✅ `configurator_item` order item type registrerad i Drupal
- ✅ Fält skapade: `field_configurator_sku` + `field_configurator_data`
- ✅ Config exporterad, commit: `[TASK-015-01]`

### SESSION 2 ✅ Klar (2026-03-18)

- ✅ `field_configurator_schema` verifierad via Drush (JSON:API kräver auth)
- ✅ `variationTypes` kopplad på båda product types
- ✅ 12 dummy-varianter skapade (CONFIGURATOR-15 → CONFIGURATOR-26)
- ✅ Cart API fungerar med `qoute` order item type
- ✅ `field_configurator_sku` + `field_configurator_data` tillagda på `qoute`
- ✅ `configurator_item` order item type borttagen (onödig)
- ✅ PriceResolver skippad — dummy-varianter har pris 0 SEK redan

### SESSION 3 — nästa

**Mãl:** JS-konfiguratorn — dropdown-rendering + dependsOn-filtrering

**Förberedelser före SESSION 3:**
- Läs schemat för MAX BASE (produkt 15) via:
  `ddev drush php:eval "echo \Drupal::entityTypeManager()->getStorage('commerce_product')->load(15)->get('field_configurator_schema')->value;"`
- Granska `/docs/product-schemas/max-configurator-schemas.json`

**Vad ska byggas:**
1. `configurator.js` — läser `field_configurator_schema` från inline JSON på sidan
2. Renderar dropdowns i ordning från `steps`-arrayen
3. `dependsOn`-filtrering — döljer ogiltiga alternativ vid val
4. `dependsOnAny`-filtrering för watt-steget (längd + CRI/chips)
5. Visuellt test med MAX BASE

**Tekniska detaljer att komma ihåg:**
- Schemat exponeras INTE via JSON:API utan auth — använd inline `<script type="application/json">`
- Drupal-modulen `tritonled_configurator` har `js/configurator.js` som platshållare
- Dummy-variation SKU: `CONFIGURATOR-{product_id}` (t.ex. `CONFIGURATOR-15` = variation ID 20841)
- Order item type: `qoute` (inte `configurator_item` — den är borttagen)
- Fält på order item: `field_configurator_sku`, `field_configurator_data`

**Godkännande krävs före:**
- JS-fil skapas i modulen
- Block/preprocess hook för inline JSON

### Kommande sessioner
| Session | Fokus |
|---------|-------|
| SESSION 3 | JS: dropdown-rendering + dependsOn-filtrering |
| SESSION 4 | JS: SKU-byggare + cart-knapp |
| SESSION 5 | Styling + placering i Layout Builder |

Se fullständig plan: `/docs/tasks/task-015-session-plan.md`  
Se task-detaljer: `/docs/tasks/task-015-variant-configurator.md`

---

## Öppna tasks

| Task | Status | Fil |
|------|--------|-----|
| TASK-015 | 🔄 SESSION 1 klar | task-015-variant-configurator.md |
| TASK-016 | ✅ Completed | task-016-navigation-styling.md |
| TASK-017 | Planned | task-017-cart-block-styling.md |
| TASK-018 | In Progress | task-018-cart-page-layout.md |
| TASK-013 | In Progress | task-013-attribut-cleanup.md |
| TASK-020 | ✅ Completed | task-020-produktarkitektur-rebuild.md |
