# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 5b KLAR, SESSION 6 väntar
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

### SESSION 5b ✅ KLAR (2026-03-23)
- ✅ Namnkonvention på media: `{imagePrefix}-{kod}` (TM-C, TM-E, TM-V, TM-B, TM-W, TM-default)
- ✅ `field_configurator_media` på `led_luminaire_max_opti` och `led_luminaire_srow`
- ✅ View mode `configurator_image` på media.image — endast bild
- ✅ preprocess-hook bygger `imagePictures` — verifierat på MAX BASE
- ✅ `ConfiguratorImageBlock` — separat placerbart block i Layout Builder
- ✅ `ConfiguratorBlock` — renderar bara konfigurator-UI
- ✅ JS `maybeUpdateImage()` byter `src`/`srcset` vid endcap-val — verifierat
- ✅ MAX BASE (produkt 15) end-to-end verifierat
- ✅ 5b-07: `field_configurator_media` på `led_luminaire_srow` — fanns redan
- ✅ 5b-09: `imagePrefix` + `visual=[endcap]` på alla 12 produkter — redan i DB
- ✅ Beslut: `color` är INTE visual — inga bilder per färg
- ✅ Beslut: SROW har BARA Cable Gland (C) — verifierat mot triton-solutions.co
- ✅ 5b-10: Alla produkter har minst default-bild — fallback fungerar
- ✅ SROW Layout Builder-layout skapad (blb_col_2, ConfiguratorImageBlock + ConfiguratorBlock)
- ✅ Båda blocken verifierade i DOM på produkt 24 (SROW BASE)
- ✅ `task-016e-bildkartlaggning.md` uppdaterad — SROW-fel rensade
- ✅ Config exporterad, commit: `[TASK-015-5b]`

---

## Nästa session — SESSION 6

Konfiguratorn är komplett på alla 12 produkter. Nästa steg:
- Verifiera konfigurator + bildväxling på ett urval av produkter (MAX-S, OPTI, SROW)
- Planera SESSION 6: live specs-display / produktseriesida (TASK-017b)

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-015 | ✅ SESSION 5b klar | Produktkonfigurator |
| TASK-016b | ✅ Completed | SKU-placeholders — löst av auto-select i SESSION 5 |
| TASK-017b | 🔄 In Progress — steg 1-3 klara | Produktseriesidor + taxonomi-struktur |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-019 | In Progress | Klaro GDPR cookie consent |
| TASK-016 | ✅ Completed | Navigation-styling |
| TASK-020 | ✅ Completed | Produktarkitektur rebuild |
