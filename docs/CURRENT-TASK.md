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

### SESSION 4 ✅ Klar (2026-03-18)
- ✅ `tritonled_configurator.routing.yml` — custom route `/triton/configurator/add-to-cart`
- ✅ `ConfiguratorCartController.php` — lägger till i cart via CartManager utan redirect
- ✅ `configurator.js` — CSRF-token hämtas, POST till custom route, feedback visas
- ✅ "Lägg i offert" fungerar end-to-end — produkt läggs i cart, stannar på sidan
- ✅ Variation ID hämtas automatiskt via preprocess hook (söker CONFIGURATOR-prefix)
- ✅ Verifierat: `M-A0C8-J19N1` är giltig kombination

### SESSION 5 — Nästa

**Mål:** Styling (Bootstrap) + bildväxling (imageMap) + generalisering

**Förberedelser gjorda:**
- ✅ Bildkartläggning klar: `/docs/tasks/task-016e-bildkartlaggning.md`
- ✅ Alla endcap-bilder identifierade per produktmodell (TM-, TMS-, TME-, TO-, TS-)
- ✅ `imageMap`-format definierat för schemat
- ✅ Saknade bilder identifierade (W3 för SROW/OPTI, CG för TME)

**Vad ska byggas:**
1. Bootstrap-styling — 3-kolumns grid, labels, SKU-bar, knapp
2. `imageMap` i `field_configurator_schema` för MAX BASE
3. JS bildväxling vid endcap-val (DOM-event `triton:configurator:image`)
4. Antal-fält (TASK-016d)
5. Auto-select första giltiga kombination (TASK-016c)
6. Placera blocket på alla 12 produktsidor
7. Test OPTI BASE + SROW BASE

**STOP — godkännande krävs** innan SESSION 5 startar.

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
