# Aktuell Task

**Task**: TASK-015 (Produktkonfigurator)
**Status**: In Progress — SESSION 3 klar, SESSION 4 nästa
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
- ✅ Beslut: schema via inline `drupalSettings` — inte JSON:API

### SESSION 3 ✅ Klar (2026-03-18)

**Vad byggdes:**
- ✅ `tritonled_configurator.libraries.yml` — registrerar `configurator.js`
- ✅ `tritonled_configurator.module` — `hook_preprocess_commerce_product` sätter `drupalSettings.tritonConfigurator` (productId + schema) och attachar library
- ✅ `src/Plugin/Block/ConfiguratorBlock.php` — block plugin renderar `<div data-triton-configurator>`, synlig i Layout Builder under kategorin "TritonLED"
- ✅ `js/configurator.js` — Drupal behavior med:
  - Dropdown-rendering i steg-ordning från schema
  - `dependsOn`-filtrering (t.ex. endcap beror på driver)
  - `dependsOnAny`-filtrering (watt beror på längd + CRI)
  - Live SKU-byggare (`M-{middle}{end}`)
  - SKU-display under dropdownsen

**Verifierat:**
- ✅ Alla 8 dropdowns renderas korrekt för MAX BASE (produkt 15)
- ✅ `dependsOn` fungerar — Cable Gland/EnstoNet tillgängliga för alla drivers, W1 bara för On/Off
- ✅ `dependsOnAny` fungerar — watt filtreras korrekt per längd
- ✅ SKU byggs live, t.ex. `M-A0C8-J19N1` verifierad som giltig kombination
- ✅ Blocket placerat i Layout Builder på MAX BASE-produktsidan

**Modulstruktur nu:**
```
tritonled_configurator/
├── tritonled_configurator.info.yml
├── tritonled_configurator.module        ← preprocess hook
├── tritonled_configurator.libraries.yml ← registrerar configurator.js
├── src/Plugin/Block/
│   └── ConfiguratorBlock.php           ← block plugin "Produktkonfigurator"
└── js/
    └── configurator.js                  ← dropdown + dependsOn + SKU-display
```

### SESSION 4 — Nästa

**Mål:** SKU-byggaren komplett + "Lägg i offert"-knapp → Cart API POST

**Vad ska byggas:**
1. "Lägg i offert"-knapp i konfiguratorn
2. Validering — alla steg måste vara valda innan POST
3. `POST /cart/add` med dummy-variation ID + `field_configurator_sku` + `field_configurator_data`
4. Success/error-feedback till användaren
5. End-to-end test: konfigurera → lägg i offert → verifiera i kundvagn

**Cart API-anrop (planerat):**
```json
POST /cart/add
[{
  "purchased_entity_type": "commerce_product_variation",
  "purchased_entity_id": "20841",
  "quantity": "1",
  "field_configurator_sku": [{"value": "M-A0C8-J19N1"}],
  "field_configurator_data": [{"value": "{\"length\":\"A\",\"driver\":\"0\",...}"}]
}]
```

**Dummy-variation per produkt:**
- Produkt 15 (MAX BASE) → variation ID 20841 (SKU: CONFIGURATOR-15)

**STOP — godkännande krävs** innan SESSION 4 startar.

### Kommande sessioner
| Session | Fokus |
|---------|-------|
| SESSION 4 | JS: Cart API POST + feedback |
| SESSION 5 | Styling (Bootstrap) + generalisering OPTI/SROW |

Se fullständig plan: `/docs/tasks/task-015-session-plan.md`

---

## Öppna tasks

| Task | Status | Fil |
|------|--------|-----|
| TASK-015 | 🔄 SESSION 4 klar | task-015-variant-configurator.md |
| TASK-016b | Planned | task-016b-konfigurator-sku-placeholder.md |
| TASK-016e | Planned | task-016e-konfigurator-bildvaxling.md |
| TASK-016c | Planned | task-016c-konfigurator-autoselect.md |
| TASK-016d | Planned | task-016d-konfigurator-antal.md |
| TASK-016 | ✅ Completed | task-016-navigation-styling.md |
| TASK-017 | Planned | task-017-cart-block-styling.md |
| TASK-018 | In Progress | task-018-cart-page-layout.md |
| TASK-013 | In Progress | task-013-attribut-cleanup.md |
| TASK-020 | ✅ Completed | task-020-produktarkitektur-rebuild.md |
