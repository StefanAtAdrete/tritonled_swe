# Task 015: Produktkonfigurator

**Skapad**: 2026-03-18  
**Status**: In Progress — SESSION 1 klar  
**Senast uppdaterad**: 2026-03-18  
**Relaterade filer**:
- `/docs/tasks/task-015-konfigurator-arkitektur.md` — Arkitekturbeslut
- `/docs/tasks/task-015-session-plan.md` — Sessionsplan (6 sessioner)
- `/docs/product-schemas/max-configurator-schemas.json`
- `/docs/product-schemas/opti-configurator-schemas.json`
- `/docs/product-schemas/srow-configurator-schemas.json`

---

## 1. DEFINE

### Mål
Bygga en produktkonfigurator för LED-armaturer som låter B2B-kunder konfigurera en produkt steg-för-steg, genererar SKU dynamiskt och lägger produkten i offert-korgen.

### Syfte
Commerce's inbyggda attribut-dropdowns hanterar inte `dependsOn`-logiken från Tritons produktscheman (villkorliga val, hierarkiska beroenden, watt beroende av längd+CRI/chips). En custom konfigurator behövs för rätt UX.

### Acceptanskriterier
- [ ] Konfiguratorn renderar dropdowns i korrekt ordning från JSON-schema
- [ ] `dependsOn`-filtrering döljer ogiltiga alternativ
- [ ] `dependsOnAny`-filtrering för watt fungerar (längd + CRI/chips)
- [ ] SKU byggs live och visas för användaren
- [ ] "Lägg i offert"-knapp lägger `configurator_item` i cart
- [ ] Fungerar för alla 3 produktfamiljer: MAX, OPTI, SROW
- [ ] Placerad som block via Layout Builder på produktsidan
- [ ] Pris döljs helt (offert-flöde)

### Godkänt av Stefan
✅ Arkitekturbeslut godkänt 2026-03-18

---

## 2. PLAN

### Arkitekturbeslut
**Ingen Commerce-variantsökning** — konfiguratorn lägger custom order item direkt i cart.

```
┌─────────────────────────────────────────────┐
│  Commerce Product (1 per modell, 12 totalt)  │
│  → field_configurator_schema (JSON)          │
├─────────────────────────────────────────────┤
│  JS Konfigurator                             │
│  → Läser schema → renderar dropdowns         │
│  → dependsOn/dependsOnAny-filtrering         │
│  → Bygger SKU live                           │
│  → POST till Cart API                        │
├─────────────────────────────────────────────┤
│  configurator_item (custom order item type)  │
│  → field_configurator_sku (det genererade)   │
│  → field_configurator_data (valen som JSON)  │
│  → purchased_entity → commerce_product       │
│  → Pris = 0 (döljs i offert-flöde)          │
└─────────────────────────────────────────────┘
```

### SKU-format
```
MAX BASE:  M-{length}{driver}{endcap}{cri}{kelvin}{watt}{optic}{color}
           Exempel: M-A0C8-J19N1

SROW BASE: S-{length}{driver}{endcap}{chips}{ipClass}{kelvin}{watt}{optic}{color}
           Exempel: S-B02Y-K30M1
```
Kelvin har bindestreck som prefix i koden (t.ex. `-J` för 3000K).

---

## 3. IMPLEMENT

### Sub-tasks

| Sub-task | Beskrivning | Status | Session |
|----------|-------------|--------|---------|
| TASK-015-01 | Skapa `tritonled_configurator` modul | ✅ Klar | SESSION 1 |
| TASK-015-02 | Skapa `configurator_item` order item type | ✅ Klar | SESSION 1 |
| TASK-015-03 | Fält på order item: `field_configurator_sku` + `field_configurator_data` | ✅ Klar | SESSION 1 |
| TASK-015-04 | Verifiera JSON:API endpoint för produkter + schema-fält | ⏳ Nästa | SESSION 2 |
| TASK-015-05 | Testa Cart API POST med `configurator_item` | ⏳ Planerad | SESSION 2 |
| TASK-015-06 | PriceResolver för `configurator_item` (returnerar 0) | ⏳ Planerad | SESSION 2 |
| TASK-015-07 | JS: schema-läsning + dropdown-rendering | ⏳ Planerad | SESSION 3 |
| TASK-015-08 | JS: `dependsOn`-filtrering | ⏳ Planerad | SESSION 3 |
| TASK-015-09 | JS: `dependsOnAny`-filtrering (watt-steget) | ⏳ Planerad | SESSION 3 |
| TASK-015-10 | JS: SKU-byggare live | ⏳ Planerad | SESSION 4 |
| TASK-015-11 | JS: "Lägg i offert"-knapp → Cart API POST | ⏳ Planerad | SESSION 4 |
| TASK-015-12 | Bootstrap-styling + dölj Commerce-dropdowns | ⏳ Planerad | SESSION 5 |
| TASK-015-13 | Placera som block i Layout Builder på produktsidan | ⏳ Planerad | SESSION 5 |
| TASK-015-14 | Test med OPTI BASE och SROW BASE | ⏳ Planerad | SESSION 5 |

---

### SESSION 1 ✅ Klar — 2026-03-18

**Vad gjordes:**
- Kontrollerade att alla 12 Commerce-produkter finns och har `field_configurator_schema` ifyllt (verifierat via Drush)
- Skapade `tritonled_configurator` modul under `web/modules/custom/`
- Skapade `configurator_item` order item type (`commerce_order.commerce_order_item_type.configurator_item.yml`)
  - `purchasableEntityType: commerce_product`
  - `orderType: default`
- Skapade `field_configurator_sku` (string) på `configurator_item`
- Skapade `field_configurator_data` (string_long/JSON) på `configurator_item`
- Modul aktiverad utan fel
- Config exporterad, commit gjord

**Produkter verifierade (12 st):**
| ID | Typ | Modell | Schema |
|----|-----|--------|--------|
| 15 | led_luminaire_max_opti | Triton MAX Gen. 3 (BASE) | ✅ 4009 chars |
| 16 | led_luminaire_max_opti | Triton MAX-PRO Gen. 3 (BASE) | ✅ 1951 chars |
| 17 | led_luminaire_max_opti | Triton MAX-S Gen. 3 (Sensor) | ✅ 4743 chars |
| 18 | led_luminaire_max_opti | Triton MAX-E Gen. 3 (Emergency) | ✅ 1663 chars |
| 19 | led_luminaire_max_opti | Triton MAX-ED + Daylight Gen. 3 | ✅ 2352 chars |
| 20 | led_luminaire_max_opti | Triton OPTI Gen. 4 (BASE) | ✅ 4204 chars |
| 21 | led_luminaire_max_opti | Triton OPTI-S Gen. 4 (Sensor) | ✅ 4786 chars |
| 22 | led_luminaire_max_opti | Triton OPTI-E Gen. 4 (Emergency) | ✅ 1768 chars |
| 23 | led_luminaire_max_opti | Triton OPTI-ED + Daylight Gen. 4 | ✅ 2473 chars |
| 24 | led_luminaire_srow | Triton SROW Gen. 3 (Base) | ✅ 3186 chars |
| 25 | led_luminaire_srow | Triton SROW-E Gen. 3 (Emergency) | ✅ 1282 chars |
| 26 | led_luminaire_srow | Triton SROW-ED Gen. 3 (ED) | ✅ 1853 chars |

**Hinder/Problem:**
- Fel filnamn på config YAML (`commerce_order_item_type.configurator_item.yml` → rätt: `commerce_order.commerce_order_item_type.configurator_item.yml`)
- Fixat och modulen aktiverades vid andra försöket

**Git commit:** `[TASK-015-01] Add tritonled_configurator module with configurator_item order type and fields`

**Modulstruktur:**
```
web/modules/custom/tritonled_configurator/
├── tritonled_configurator.info.yml
├── tritonled_configurator.module
├── config/install/
│   └── commerce_order.commerce_order_item_type.configurator_item.yml
└── js/
    └── configurator.js   ← platshållare, SESSION 3
```

---

## 4. VERIFY

*(Fylls i när SESSION 5 är klar)*

---

## 5. NÄSTA STEG — SESSION 2

**Mål:** Verifiera JSON:API + bekräfta att Cart API fungerar med `configurator_item`

1. Testa JSON:API-endpoint:
   `GET /jsonapi/commerce_product/led_luminaire_max_opti?fields[commerce_product--led_luminaire_max_opti]=field_configurator_schema,title`
2. Bekräfta att `field_configurator_schema` returneras i JSON:API-svaret
3. Testa Cart API POST med `configurator_item` och produktreferens (produkt ID 15)
4. Implementera PriceResolver som returnerar 0 för `configurator_item`
