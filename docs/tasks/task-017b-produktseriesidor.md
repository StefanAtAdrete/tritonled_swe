# TASK-017b — Produktseriesidor + Taxonomi-struktur

**Skapad**: 2026-03-23
**Status**: 🔄 In Progress — Steg 1-3 klara (2026-03-23)
**Prioritet**: Hög
**Relaterad**: TASK-015 (Konfigurator), TASK-013 (Attribut-cleanup)

---

## Mål

Bygga en produktkatalog med två nivåer:
1. **Översiktssida** — visar alla produktserier (MAX, OPTI, SROW)
2. **Seriesida** — visar undermodellerna per serie (BASE, Sensor, Emergency, ED)

Inspirerad av: `https://triton-solutions.co/sw/catalogue/industrial/linear`

---

## Arkitektur

### Taxonomier (3 st)

| Vocabulary | Maskinnamn | Terms | Fält på produkt | Status |
|-----------|-----------|-------|----------------|--------|
| Produktserier | `product_categories` | MAX, OPTI, SROW | `field_product_categories` | ✅ Vocabulary finns — lägg till terms |
| Produkttyper | `product_type` | Base, Sensor, Emergency, Emergency Daylight | `field_product_type` | ❌ Ny vocabulary + nytt fält |
| Tillverkare | `producers` | TritonLED | `field_producers` | ✅ Finns — lägg till term TritonLED |

### Koppling produkter → taxonomi

| Produkt-ID | Titel | Serie | Typ |
|-----------|-------|-------|-----|
| 15 | MAX BASE | MAX | Base |
| 16 | MAX-PRO BASE | MAX | Base |
| 17 | MAX-S Sensor | MAX | Sensor |
| 18 | MAX-E Emergency | MAX | Emergency |
| 19 | MAX-ED Emergency Daylight | MAX | Emergency Daylight |
| 20 | OPTI BASE | OPTI | Base |
| 21 | OPTI-S Sensor | OPTI | Sensor |
| 22 | OPTI-E Emergency | OPTI | Emergency |
| 23 | OPTI-ED Emergency Daylight | OPTI | Emergency Daylight |
| 24 | SROW BASE | SROW | Base |
| 25 | SROW-E Emergency | SROW | Emergency |
| 26 | SROW-ED Emergency Daylight | SROW | Emergency Daylight |

---

## Sidstruktur

| URL | Innehåll | Lösning |
|-----|---------|---------|
| `/produkter` | Alla serier (MAX, OPTI, SROW) som stora kort | Views page |
| `/produkter/max` | MAX-undermodeller (BASE, PRO, Sensor, Emergency, ED) | Views taxonomy term page |
| `/produkter/opti` | OPTI-undermodeller | Views taxonomy term page |
| `/produkter/srow` | SROW-undermodeller | Views taxonomy term page |
| `/produkter/sok` | Sökresultat med facets | Search API + Facets (framtida) |

---

## Implementation — steg-för-steg

### Steg 1 — Taxonomy terms ✅
- MAX (tid=13), OPTI (tid=14), SROW (tid=15) i `product_categories`
- Ny vocabulary `product_type`: Base (16), Sensor (17), Emergency (18), Emergency Daylight (19)
- TritonLED (tid=20) i `producers`
- Config exporterad, commit: `[TASK-017b-01]`

### Steg 2 — Nytt fält `field_product_type` ✅
- Entity reference → `product_type` taxonomy
- Tillagd på `led_luminaire_max_opti` och `led_luminaire_srow`
- Också lade till `field_product_categories` och `field_producers` på `led_luminaire_srow` (saknades)

### Steg 3 — Koppla produkter till taxonomi ✅
- Alla 12 produkter kopplade korrekt till serie, typ och producent
- Verifierat via Drush

### Steg 4 — View mode för produktkort
- Skapa view mode `series_card` på `commerce_product`
- Visar: bild (field_product_media), titel, kort beskrivning, länk
- Via admin UI → `cex`

### Steg 5 — Views
- **Översiktssida** (`/produkter`): grupperar på `field_product_categories`, ett kort per serie
- **Seriesida** (`/produkter/%`): taxonomy term page, filtrerar på `field_product_categories`
- Bootstrap Layout Builder-klasser för layout

---

## Acceptanskriterier

- [ ] `/produkter` visar MAX, OPTI, SROW med bild, titel, beskrivning och länk
- [ ] `/produkter/max` visar alla MAX-modeller som kort med länk till produktsida
- [ ] `/produkter/opti` och `/produkter/srow` fungerar likadant
- [ ] Filtrering på `field_product_type` möjlig i Views (förberedd för Search API)
- [ ] `field_producers` kopplad till TritonLED på alla 12 produkter
- [ ] Config exporterad och committad

---

## Öppna frågor

| Fråga | Svar |
|-------|------|
| URL-struktur för seriesidor? | ✅ `/produkter/serie/max` etc. |
| Ska `field_series` (string) behållas eller tas bort? | Avgörs i TASK-013 |
| Ska taxonomy-termsidorna ha Layout Builder? | Bestäms vid implementation |
| Vilken bild visas på seriekortet på översiktssidan? | ✅ Standardbilden (default, oftast Cable Gland) |
