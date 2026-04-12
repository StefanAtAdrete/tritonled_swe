# Aktuell Task

**Senast uppdaterad**: 2026-04-12

---

## ⏭️ Nästa session startar här: TASK-029 Fas 3 fortsätter

**Läs**: `docs/tasks/task-029-produkttyper-faltstruktur.md`

**Fas 1 klar** ✅
**Fas 2 klar** ✅
**Fas 3 Highbay klar** ✅ (2026-04-12)

### Highbay (klar) ✅
- 6 produkter + 72 variationer importerade
- `attribute_watt` används som variantväljare
- `feeds_item` + `field_product_sku` tillagda på highbay bundle
- CSV: `data/highbay_products.csv` + `data/highbay_variations.csv`
- Feeds: `tritonled_highbay_products` + `tritonled_highbay_variations`

### Fas 3 — Återstående produkttyper
| Produkttyp | Status |
|---|---|
| `highbay` | ✅ Klar |
| `floodlight` | ⏳ Nästa |
| `high_mast` | ⏳ |
| `street_area` | ⏳ |
| `ex_hazardous` | ⏳ |
| `linear_led` (Triproof) | ⏳ |
| `led_panel` (ny typ?) | ⏳ |
| `accessories` | ⏳ |

**Lärdomar från highbay:**
- `feeds_item` måste läggas till manuellt på varje ny bundle (produkt + variation)
- `field_product_sku` måste läggas till manuellt på varje ny produkttyp
- `attribute_watt` används som variantväljare — inte `field_watt`
- Order item type måste sättas till `quote` på variation type
- Alla nya variation bundles behöver `feeds_item` + relevanta `field_*`

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-029 | 🔄 In Progress | Fas 3: CSV-mallar & feeds per produkttyp |
| TASK-018 | 🔄 In Progress | Cart page layout (surge_protection order item type fix) |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |
