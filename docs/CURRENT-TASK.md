# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 5b delvis klar
**Senast uppdaterad**: 2026-03-21

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

### SESSION 4 ✅ Klar (2026-03-18)
- ✅ `tritonled_configurator.routing.yml` — custom route `/triton/configurator/add-to-cart`
- ✅ `ConfiguratorCartController.php` — lägger till i cart via CartManager utan redirect
- ✅ `configurator.js` — CSRF-token hämtas, POST till custom route, feedback visas
- ✅ "Lägg i offert" fungerar end-to-end — produkt läggs i cart, stannar på sidan
- ✅ Variation ID hämtas automatiskt via preprocess hook (söker CONFIGURATOR-prefix)
- ✅ Verifierat: `M-A0C8-J19N1` är giltig kombination

### SESSION 5 ✅ Klar (2026-03-20)
- ✅ Bootstrap-styling (row g-3, form-select, btn btn-primary, SKU-bar)
- ✅ Auto-select med loop — fyller alla beroende steg vid sidladdning och vid val-byte
- ✅ Antal-fält (input[type=number] + quantity i CartController)
- ✅ Dölj Commerce egna dropdowns
- ✅ Cart POST + feedback fungerar end-to-end

### SESSION 5b ✅ Klar för MAX BASE (2026-03-21)
- ✅ Namnkonvention på media: `{imagePrefix}-{kod}` (TM-C, TM-E, TM-V, TM-B, TM-W, TM-default)
- ✅ `field_configurator_media` på `led_luminaire_max_opti`
- ✅ View mode `configurator_image` på media.image — endast bild (author/datum/thumbnail disabled)
- ✅ preprocess-hook bygger `imagePictures` — 6 poster per produkt, verifierat
- ✅ `ConfiguratorImageBlock` — separat placerbart block i Layout Builder (`tritonled_configurator_image_block`)
- ✅ `ConfiguratorBlock` — rensat, renderar bara konfigurator-UI
- ✅ JS `maybeUpdateImage()` byter `src`/`srcset` vid endcap-val — verifierat
- ✅ MAX BASE end-to-end verifierat
- ✅ Deploy: DB + filer synkade till produktion, Views-config exporterad

**⏳ Återstår SESSION 5b:**
- `field_configurator_media` på `led_luminaire_srow` (5b-07)
- `imagePrefix` + `visual`-flagga i schema på produkter 16–26 (5b-09)
- Ladda upp och koppla media till produkter 16–26 (5b-10)

**Se fullständig plan:** `/docs/tasks/task-015-session-5b-bildvaxling.md`

---

## Nästa session startar med
5b-07 → 5b-09 → 5b-10 → placera båda blocken på alla 12 produktsidor → SESSION 6

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-015 | 🔄 SESSION 5b delvis klar | Produktkonfigurator |
| TASK-015 SESSION 5b | 🔄 MAX BASE klar | field_configurator_media + bildväxling övriga produkter |
| TASK-016b | Planned | SKU-placeholders vid ovalda steg |
| TASK-017b | Planned | Live specs-display + produktseriesida (View) |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-019 | In Progress | Klaro GDPR cookie consent |
| TASK-016 | ✅ Completed | Navigation-styling |
| TASK-020 | ✅ Completed | Produktarkitektur rebuild |
