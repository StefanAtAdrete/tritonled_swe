# Aktuell Task

**Task**: TASK-017b (Produktseriesidor + Views)
**Status**: In Progress — SESSION 2 påbörjad (2026-03-24)
**Senast uppdaterad**: 2026-03-24

---

## Var vi är

### SESSION 1 ✅ Klar (2026-03-23)
- ✅ Taxonomier: MAX (tid=13), OPTI (tid=14), SROW (tid=15) i `product_categories`
- ✅ Taxonomy `product_type`: Base (16), Sensor (17), Emergency (18), Emergency Daylight (19)
- ✅ Taxonomy `producers`: TritonLED (tid=20)
- ✅ `field_product_type` tillagd på båda produkttyperna
- ✅ Alla 12 produkter kopplade: serie, typ, producent
- ✅ View mode `series_card` skapad
- ✅ Vy `produktserier` skapad (URL: `/produkter`)

### SESSION 2 🔄 In Progress (2026-03-24)
- ✅ Ny taxonomy-term `PRO` (tid=21) skapad i `product_type`
- ✅ MAX-PRO (produkt 16) fick `field_product_type = PRO`
- ✅ Filter i `featured_products`-vyn: `field_product_type = Base` → visar nu 3 kort (MAX, OPTI, SROW)
- ✅ Tre separata Views-block (block_max, block_opti, block_srow) med list groups
- ✅ List groups: bild + serienamn + undermodeller med länk per rad
- ✅ cards.css uppdaterad: 16:9 aspect-ratio på alla skärmstorlekar
- ✅ TASK-021, TASK-022, TASK-023 dokumenterade
- ✅ Config exporterad, commit: `[TASK-017b] Filter Featured Products to BASE only, add PRO product_type term`

**Nästa steg — nästa session:**
- `ddev drush cex -y` + commit för Views-ändringar (list groups, cards.css)
- TASK-023: Konfigurator mobiloptimering (Bootstrap custom dropdowns)
- TASK-021: Syskonprodukter-block på produktsidan

---

## Produkthierarki (verifierad 2026-03-24)

| ID | Titel | Serie | Typ |
|----|-------|-------|-----|
| 15 | Triton MAX | MAX | Base |
| 16 | Triton MAX-PRO | MAX | PRO |
| 17 | Triton MAX-S Gen. 3 (Sensor) | MAX | Sensor |
| 18 | Triton MAX-E Gen. 3 (Emergency) | MAX | Emergency |
| 19 | Triton MAX-ED + Daylight Gen. 3 | MAX | Emergency Daylight |
| 20 | Triton OPTI | OPTI | Base |
| 21 | Triton OPTI-S Gen. 4 (Sensor) | OPTI | Sensor |
| 22 | Triton OPTI-E Gen. 4 (Emergency) | OPTI | Emergency |
| 23 | Triton OPTI-ED + Daylight Gen. 4 | OPTI | Emergency Daylight |
| 24 | Triton SROW IP54/IP65 | SROW | Base |
| 25 | Triton SROW-E Gen. 3 (Emergency) | SROW | Emergency |
| 26 | Triton SROW-ED Gen. 3 (Emergency + Daylight) | SROW | Emergency Daylight |

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-015 | ✅ SESSION 5b klar | Produktkonfigurator |
| TASK-016b | ✅ Completed | SKU-placeholders |
| TASK-017b | 🔄 In Progress — SESSION 2 | Produktseriesidor + Views |
| TASK-021 | Planned | Syskonprodukter-block på produktsidan |
| TASK-022 | Planned | Översättning SV/EN — block, innehåll, produkter |
| TASK-023 | Planned | Konfigurator mobiloptimering — Bootstrap dropdowns |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-019 | In Progress | Klaro GDPR cookie consent |
| TASK-016 | ✅ Completed | Navigation-styling |
| TASK-020 | ✅ Completed | Produktarkitektur rebuild |
