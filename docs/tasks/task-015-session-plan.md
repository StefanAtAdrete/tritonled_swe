# TASK-015: Produktkonfigurator — Sessionsplan

**Skapad**: 2026-03-18  
**Uppdaterad**: 2026-03-18  
**Status**: In Progress — SESSION 3 klar, SESSION 4 nästa

---

## Kontext & förutsättningar

### Vad som är klart
- ✅ Commerce Product Types: `led_luminaire_max_opti` och `led_luminaire_srow`
- ✅ `field_configurator_schema` (Long text) finns på båda typerna
- ✅ 12 Commerce-produkter skapade (en per modell), alla med schema ifyllt
- ✅ JSON-scheman finns i `/docs/product-schemas/` (max, opti, srow)
- ✅ Scheman är kompletta med `dependsOn`/`dependsOnAny`-logik
- ✅ `tritonled_configurator` modul aktiverad
- ✅ Order item type: `qoute` (standard Commerce) med extra fält
- ✅ Fält på `qoute`: `field_configurator_sku` (string), `field_configurator_data` (text_long)
- ✅ 12 dummy-varianter skapade (CONFIGURATOR-15 → CONFIGURATOR-26)
- ✅ Cart API fungerar med `qoute` order item type
- ✅ Block plugin `ConfiguratorBlock` — synlig i Layout Builder under "TritonLED"
- ✅ `configurator.js` — dropdowns + dependsOn + dependsOnAny + live SKU-display
- ✅ Blocket placerat på MAX BASE-produktsidan, verifierat i webbläsaren

### Arkitekturbeslut
- **Order item type**: `qoute` med extra fält `field_configurator_sku` + `field_configurator_data`
- **Schema**: exponeras via `drupalSettings.tritonConfigurator` (sätts i preprocess hook)
- **Block**: `ConfiguratorBlock` plugin renderar `<div data-triton-configurator>`
- **JS**: vanilla JS Drupal behavior, inga externa bibliotek

### Modulstruktur (aktuell)
```
tritonled_configurator/
├── tritonled_configurator.info.yml
├── tritonled_configurator.module        ← hook_preprocess_commerce_product
├── tritonled_configurator.libraries.yml ← registrerar configurator.js
├── src/Plugin/Block/
│   └── ConfiguratorBlock.php           ← block plugin "Produktkonfigurator"
└── js/
    └── configurator.js                  ← dropdown + dependsOn + SKU-display
```

### Dummy-variation-mappning
| Produkt ID | Produkt | Variation SKU | Variation ID |
|------------|---------|---------------|--------------|
| 15 | MAX BASE | CONFIGURATOR-15 | 20841 |
| 16 | MAX-PRO | CONFIGURATOR-16 | 20842 |
| 17 | MAX-S | CONFIGURATOR-17 | 20843 |
| 18 | MAX-E | CONFIGURATOR-18 | 20844 |
| 19 | MAX-ED | CONFIGURATOR-19 | 20845 |
| 20 | OPTI BASE | CONFIGURATOR-20 | 20846 |
| 21 | OPTI-S | CONFIGURATOR-21 | 20847 |
| 22 | OPTI-E | CONFIGURATOR-22 | 20848 |
| 23 | OPTI-ED | CONFIGURATOR-23 | 20849 |
| 24 | SROW BASE | CONFIGURATOR-24 | 20850 |
| 25 | SROW-E | CONFIGURATOR-25 | 20851 |
| 26 | SROW-ED | CONFIGURATOR-26 | 20852 |

### SKU-format
```
MAX:  {skuPrefix}{middle-koder}{end-koder}
      Exempel: M-A0C8-J19N1
      middle = length+driver+endcap+cri = A+0+C+8 = A0C8
      end    = kelvin+watt+optic+color  = -J+19+N+1 = -J19N1

SROW: S-{length}{driver}{endcap}{chips}{ipClass}{kelvin}{watt}{optic}{color}
```

---

## Sessionsöversikt

| Session | Fokus | Status |
|---------|-------|--------|
| SESSION 1 | Backend: modul + order item type + fält | ✅ Klar |
| SESSION 2 | Backend: dummy-varianter + Cart API-verifiering | ✅ Klar |
| SESSION 3 | JS: dropdown-rendering + dependsOn + SKU-display | ✅ Klar |
| SESSION 4 | JS: Cart API POST + feedback | 🔄 Nästa |
| SESSION 5 | Styling (Bootstrap) + generalisering OPTI/SROW | ⏳ Planerad |

---

## SESSION 1 ✅ Klar — 2026-03-18

- Skapade `tritonled_configurator` modul
- Skapade `configurator_item` order item type (senare ersatt av `qoute`)
- Skapade `field_configurator_sku` + `field_configurator_data`
- **Commit:** `[TASK-015-01] Add tritonled_configurator module with configurator_item order type and fields`

---

## SESSION 2 ✅ Klar — 2026-03-18

- Verifierade `field_configurator_schema` via Drush
- Skapade 12 dummy-varianter (CONFIGURATOR-15..26)
- Verifierade Cart API med `qoute`
- Tog bort `configurator_item` — `qoute` räcker
- Beslut: schema via `drupalSettings` — inte JSON:API (kräver auth)

---

## SESSION 3 ✅ Klar — 2026-03-18

**Mål:** JS-konfiguratorn renderar dropdowns med dependsOn-filtrering och live SKU

### Vad byggdes
- ✅ `tritonled_configurator.libraries.yml` — registrerar `configurator.js`
- ✅ `tritonled_configurator.module` — `hook_preprocess_commerce_product`:
  - Sätter `drupalSettings.tritonConfigurator.productId` + `.schema`
  - Attachar `tritonled_configurator/configurator` library
  - Körs bara om produkten har `field_configurator_schema` ifyllt
- ✅ `src/Plugin/Block/ConfiguratorBlock.php`:
  - Renderar `<div data-triton-configurator id="triton-configurator">`
  - Synlig i Layout Builder under kategorin "TritonLED"
  - Cache contexts: `url.path` + produktens cache tags
- ✅ `js/configurator.js` — `Drupal.behaviors.tritonConfigurator`:
  - Läser schema från `drupalSettings.tritonConfigurator`
  - Renderar alla steps som `<select>`-dropdowns i ordning
  - `dependsOn`-filtrering: option visas om ALLA villkor uppfylls
  - `dependsOnAny`-filtrering: option visas om MINST ETT yttervillkor uppfylls
  - Rensar downstream-val vid ändring
  - Live SKU-display: `{skuPrefix}{middle-koder}{end-koder}`

### Verifierat i webbläsaren
- ✅ Alla 8 dropdowns renderas för MAX BASE (produkt 15)
- ✅ endcap-filtrering: W1 bara vid On/Off, Wago-varianter bara vid DALI
- ✅ watt-filtrering: korrekt per längd (19/22/29W vid 0,5m osv)
- ✅ SKU `M-A0C8-J19N1` verifierad som giltig kombination
- ✅ Blocket placerat i Layout Builder på MAX BASE

### Hinder/beslut under SESSION 3
- Basic block med body-fält escapade HTML → löst med block plugin istället
- `drupalSettings` är rätt kanal (inte inline `<script>` som planerat)

---

## SESSION 4 — Nästa

**Mål:** "Lägg i offert"-knapp → Cart API POST → feedback

### Sub-tasks
- TASK-015-17: Validering — alla steg måste vara valda
- TASK-015-18: "Lägg i offert"-knapp i JS
- TASK-015-19: `POST /cart/add` med variation ID + fält
- TASK-015-20: Hämta rätt variation ID per produkt (från drupalSettings eller data-attribut)
- TASK-015-21: Success-meddelande / felhantering
- TASK-015-22: End-to-end test MAX BASE → kundvagn

### Cart API-anrop (planerat)
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

**Öppen fråga:** Variation ID behöver vara känt i JS. Enklast via `drupalSettings.tritonConfigurator.variationId` — sätts i preprocess-hooken via Drush-query mot variationerna.

**STOP — godkännande krävs** innan SESSION 4 startar.

---

## SESSION 5 — Planerad

**Mål:** Bootstrap-styling + generalisering till alla 12 produkter

### Sub-tasks
- TASK-015-23: Bootstrap-styling (3-kolumns grid, labels, SKU-bar, knapp)
- TASK-015-24: Dölj Commerce's egna attribut-dropdowns på produktsidan
- TASK-015-25: Responsiv layout (mobil → 1 kolumn, desktop → 3 kolonner)
- TASK-015-26: Placera blocket på alla 12 produktsidor i Layout Builder
- TASK-015-27: Test OPTI BASE (produkt 20) + SROW BASE (produkt 24)
- TASK-015-28: Verifiering — alla 12 produkter fungerar

---

## Öppna frågor

| Fråga | Svar | Session |
|-------|------|---------|
| Schema ifyllt på alla 12? | ✅ Ja | SESSION 1 |
| Dummy-varianter finns? | ✅ Ja | SESSION 2 |
| Order item type? | `qoute` med extra fält | SESSION 2 |
| Schema-exponering? | `drupalSettings` via preprocess | SESSION 3 |
| Var renderas konfiguratorn? | Block plugin i Layout Builder | SESSION 3 |
| Variation ID i JS? | ⏳ Via drupalSettings i SESSION 4 | SESSION 4 |
| Commerce dropdowns döljas? | ⏳ SESSION 5 | SESSION 5 |
