# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 5b nästan klar
**Senast uppdaterad**: 2026-03-23

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

### SESSION 5b ✅ Nästan klar (2026-03-23)
- ✅ Namnkonvention på media: `{imagePrefix}-{kod}` (TM-C, TM-E, TM-V, TM-B, TM-W, TM-default)
- ✅ `field_configurator_media` på `led_luminaire_max_opti` och `led_luminaire_srow`
- ✅ View mode `configurator_image` på media.image — endast bild
- ✅ preprocess-hook bygger `imagePictures` — verifierat på MAX BASE
- ✅ `ConfiguratorImageBlock` — separat placerbart block i Layout Builder
- ✅ `ConfiguratorBlock` — renderar bara konfigurator-UI
- ✅ JS `maybeUpdateImage()` byter `src`/`srcset` vid endcap-val — verifierat
- ✅ MAX BASE (produkt 15) end-to-end verifierat
- ✅ Deploy: DB + filer synkade till produktion
- ✅ 5b-07: `field_configurator_media` på `led_luminaire_srow` — fanns redan
- ✅ 5b-09: `imagePrefix` på alla 12 produkter (15–26) — redan i DB
- ✅ 5b-09: `visual: true` på `endcap` för alla 12 produkter — redan i DB
- ✅ Beslut: `color` är INTE visual — inga bilder per färg finns

**⏳ Återstår SESSION 5b:**
- 5b-10: Kartlägg vilka bilder som finns → ladda upp och koppla till produkter 16–26

**Se fullständig plan:** `/docs/tasks/task-015-session-5b-bildvaxling.md`

---

## Nästa session startar med
5b-10: Kartlägg befintliga bilder → ladda upp → koppla till produkter 16–26 → placera båda blocken på alla 12 produktsidor → SESSION 6

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-015 | 🔄 SESSION 5b nästan klar | Produktkonfigurator |
| TASK-015 SESSION 5b | 🔄 5b-10 återstår | Bilder för produkter 16–26 |
| TASK-016b | Planned | SKU-placeholders vid ovalda steg |
| TASK-017b | Planned | Live specs-display + produktseriesida (View) |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-019 | In Progress | Klaro GDPR cookie consent |
| TASK-016 | ✅ Completed | Navigation-styling |
| TASK-020 | ✅ Completed | Produktarkitektur rebuild |
