# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 4 klar, SESSION 5 nästa
**Senast uppdaterad**: 2026-03-18

---

## Var vi är

### SESSION 1 ✅ Klar (2026-03-18)
- ✅ `tritonled_configurator` modul skapad och aktiverad
- ✅ Fält skapade: `field_configurator_sku` + `field_configurator_data` på `qoute`
- ✅ Config exporterad, commit: `[TASK-015-01]`

### SESSION 2 ✅ Klar (2026-03-18)
- ✅ 12 dummy-varianter skapade (CONFIGURATOR-15 → CONFIGURATOR-26)
- ✅ Cart API fungerar med `qoute` order item type
- ✅ Beslut: schema via `drupalSettings` — inte JSON:API

### SESSION 3 ✅ Klar (2026-03-18)
- ✅ `tritonled_configurator.libraries.yml` skapad
- ✅ `hook_preprocess_commerce_product` — sätter `drupalSettings.tritonConfigurator`
- ✅ `ConfiguratorBlock.php` — block plugin synlig i Layout Builder under "TritonLED"
- ✅ `configurator.js` — dropdowns + dependsOn + dependsOnAny + live SKU-display
- ✅ Blocket placerat på MAX BASE-produktsidan, verifierat i webbläsaren

### SESSION 5 ✅ Klar (2026-03-20)
- ✅ Bootstrap-styling (row g-3, form-select, btn btn-primary, SKU-bar)
- ✅ Auto-select med loop — fyller alla beroende steg vid sidladdning och vid val-byte
- ✅ Antal-fält (input[type=number] + quantity i CartController)
- ✅ Dölj Commerce egna dropdowns
- ✅ imageMap i endcap-steget för MAX BASE (temporär lösning, ersätts av SESSION 5b)
- ✅ JS bildväxling — maybeUpdateImage() med liveStep-fix
- ✅ Cart POST + feedback fungerar end-to-end
- ⚠️ Bildväxling via imageMap ersätts av SESSION 5b (namnkonvention + responsive image)

### SESSION 4 ✅ Klar (2026-03-18)
- ✅ `tritonled_configurator.routing.yml` — custom route `/triton/configurator/add-to-cart`
- ✅ `ConfiguratorCartController.php` — lägger till i cart via CartManager utan redirect
- ✅ `configurator.js` — CSRF-token hämtas, POST till custom route, feedback visas
- ✅ "Lägg i offert" fungerar end-to-end — produkt läggs i cart, stannar på sidan
- ✅ Variation ID hämtas automatiskt via preprocess hook (söker CONFIGURATOR-prefix)
- ✅ Verifierat: `M-A0C8-J19N1` är giltig kombination

### SESSION 5b — Klar (delvis)

**Mål:** Responsive Image bildväxling via namnkonvention + field_configurator_media

**Implementerat ✅:**
- `field_configurator_media` på produkten med namnkonvention `{imagePrefix}-{kod}`
- Media omdöpta: TM-C, TM-E, TM-V, TM-B, TM-W, TM-default
- View mode `configurator_image` på media.image — endast bild, inget author/datum/thumbnail
- `imagePictures` byggs i preprocess-hook — 6 poster per produkt (verifierat)
- `ConfiguratorImageBlock` — separat placerbart block i Layout Builder (`tritonled_configurator_image_block`)
- `ConfiguratorBlock` — rensat, renderar bara konfigurator-UI
- JS `maybeUpdateImage()` byter `src`/`srcset` på `<img>` inuti `.triton-configurator-image`
- MAX BASE end-to-end verifierat ✅

**⏳ Återstår:**
- `field_configurator_media` på led_luminaire_srow (5b-07)
- imagePrefix + visual på produkter 16–26 (5b-09)
- Koppla media till respektive produkt (5b-10)

**Se fullständig plan:** `/docs/tasks/task-015-session-5b-bildvaxling.md`

### Kommande sessioner
| Session | Fokus |
|---------|-------|
| SESSION 5 | Styling + imageMap + bildväxling + antal + autoselect |
| SESSION 6 | Produktseriesida (View) + specs-display live (TASK-017b) |

Se fullständig plan: `/docs/tasks/task-015-session-plan.md`

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-015 | 🔄 SESSION 4 klar | Produktkonfigurator |
| TASK-016b | Planned | SKU-placeholders vid ovalda steg |
| TASK-016c | Planned | Auto-select första giltiga kombination |
| TASK-016d | Planned | Antal-fält i konfiguratorn |
| TASK-016e | Planned | Bildväxling vid endcap/color-val |
| TASK-017b | Planned | Live specs-display + produktseriesida (View) |
| TASK-016 | ✅ Completed | Navigation-styling |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-020 | ✅ Completed | Produktarkitektur rebuild |
