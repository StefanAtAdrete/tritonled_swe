# Task 031: Site Audit — Fält, struktur & UX-beredskap

**Created**: 2026-04-15
**Status**: In Progress
**Last Updated**: 2026-04-15
**Related Tasks**: TASK-013 (Attribut-cleanup), TASK-029 (Produkttyper), TASK-030 (UX Thomas)

---

## Syfte

Innan vi bygger vidare (Cards, Badges, filtrering, agenter) behöver vi förstå
exakt hur sajten är uppbyggd idag. Auditen dokumenterar nuläget, identifierar
brister och lägger grunden för:

1. UX-testning
2. Rensning & normalisering
3. Automatisering via agenter (Drupal AI eller externa)

---

## Arkitekturprinciper (beslutade 2026-04-15)

### Tre produktkategorier med olika behandling

**Kategori A — Egna produkter med konfigurator**
`led_luminaire_max_opti`, `led_luminaire_srow`
- 55 000+ varianter via JSON-schema
- Konfigurator med bildväxling
- Särbehandlas i layout, UX och API-output
- Kräver `field_configurator_schema` + `field_configurator_media`
- `field_product_type` används för subtyper: Base, Emergency, Emergency Daylight, PRO, Sensor

**Kategori B — Egna specialprodukter**
`surge_protection`
- Egna fälttyper (`field_spd_type`, `field_current_type`)
- Inga externa leverantörer

**Kategori C — Externa/partner-produkter**
`highbay`, `floodlight`, `high_mast`, `street_area`, `ex_hazardous`, `linear_led`, `accessories`
- Importeras via CSV från externa leverantörer
- `field_brand` = varumärke på produkten
- `field_producers` = partner/tillverkare (extern leverantör eller framtida partner)

### Öppen JSON-arkitektur (mål)
Sajten ska exponera produktdata som öppna JSON-strömmar för:
- AI-agenter
- Externa e-commercesystem
- Partnerapplikationer

Drupal JSON:API (core) är basen. Alla fält som ska exponeras måste vara
korrekt namngivna, ha läsbara labels och följa konsekvent namngivning.
**Konsekvens: Fältnamn och labels är extra viktiga — de blir API-kontrakt.**

---

## 031-A: Sajtens status — direkta fel & brister
**Status**: ✅ Grundstatus klar — fylls på löpande

### Systemstatus (2026-04-15)
- Drupal: 11.3.6 ✅
- PHP: 8.4.4 ✅
- DB: MySQL 8.0 ✅ (migrerad från MariaDB 10.11)
- Drush: 13.7.2 ✅
- DB-uppdateringar: Inga behövs ✅
- Error-loggar: Inga fel ✅
- `config:status`: Kraschade (Drush 13 bugg med färgformatering) ⚠️

### Kända fel
*(Fylls på löpande)*

### Kända brister
- `config:status` fungerar inte med Drush 13 — kan inte enkelt verifiera config-synk
*(Fylls på löpande)*

---

## 031-B: Fält per produkttyp
**Status**: ✅ Komplett — produkt + variationsnivå kartlagda

### Produkttyper (commerce_product) — bundle-namn

| Bundle (machine name) | Kategori | Notering |
|---|---|---|
| `led_luminaire_max_opti` | A | Ej `max`/`opti` som tidigare antagits |
| `led_luminaire_srow` | A | Ej `srow` |
| `surge_protection` | B | |
| `highbay` | C | |
| `floodlight` | C | Inkomplett |
| `high_mast` | C | Inkomplett |
| `street_area` | C | Inkomplett |
| `ex_hazardous` | C | Inkomplett |
| `linear_led` | C | Mest komplett av de nya |
| `accessories` | C | Minimal fältuppsättning |
| `default` | ? | Okänd användning — kontrollera |

### Variationstyper (commerce_product_variation) — bundle-namn

| Bundle (machine name) | Notering |
|---|---|
| `led_luminaire_max_opti` | Samma namn som produkttypen |
| `led_luminaire_srow` | |
| `surge_protection` | OBS: heter INTE `surge_protection_variation` |
| `highbay_variation` | |
| `floodlight_variation` | |
| `high_mast_variation` | |
| `street_area_variation` | |
| `ex_hazardous_variation` | |
| `linear_led_variation` | |
| `accessories_variation` | |
| `default` | |

---

### Fältmatris — commerce_product (custom fält per bundle)

| Fält | led_luminaire_max_opti | led_luminaire_srow | surge_protection | highbay | floodlight | high_mast | street_area | ex_hazardous | linear_led | accessories |
|---|---|---|---|---|---|---|---|---|---|---|
| `body` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `feeds_item` | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_brand` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_cable` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| `field_configurator_media` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_configurator_schema` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_current_type` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_datasheet` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_dimming` | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `field_driver` | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `field_features` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_hero_media` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_ip_class` | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `field_mounting_type` | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_producers` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_product_categories` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_product_category` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `field_product_media` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `field_product_media_files` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_product_sku` | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_product_type` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_series` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_short_description` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_show_in_hero` | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_spd_type` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_warranty` | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `layout_builder__layout` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |

---

### Fältmatris — commerce_product_variation (custom fält per bundle)

#### Attribut (entity_reference → attribute_*)

| Attribut | led_luminaire_max_opti | led_luminaire_srow | surge_protection | highbay_variation | floodlight_variation | high_mast_variation | street_area_variation | ex_hazardous_variation | linear_led_variation | accessories_variation |
|---|---|---|---|---|---|---|---|---|---|---|
| `attribute_chips` | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_color` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_cri` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_driver` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_endcap` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_ip_class` | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_kelvin` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_length` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_optic` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_pairs` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_poles` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_sensor` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_voltage_un` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `attribute_watt` | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |

#### Fält (field_*)

| Fält | led_luminaire_max_opti | led_luminaire_srow | surge_protection | highbay_variation | floodlight_variation | high_mast_variation | street_area_variation | ex_hazardous_variation | linear_led_variation | accessories_variation |
|---|---|---|---|---|---|---|---|---|---|---|
| `feeds_item` | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_beam_angle` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_cable` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_cct` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_cri` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `field_dimming` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_discharge_imax` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_discharge_in` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_driver` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `field_fuse_rating` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_impulse_iimp` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_isccr` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_lumens` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `field_mounting_type` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| `field_protection_level_up` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_variation_media` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `field_voltage_uc` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `field_warranty_years` | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `layout_builder__layout` | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |

---

### Viktiga fynd — variationer

**⚠️ Kategori A saknar variations-fält**
`led_luminaire_max_opti` och `led_luminaire_srow` hanterar specs via JSON-schema
och attribut — inte via `field_*` på variationer. Detta är korrekt för konfiguratorn.

**⚠️ Etikettbrister på variationsfält**
| Fält | Bör bli |
|---|---|
| `field_cri` | "CRI" |
| `field_lumens` | "Lumen" |
| `field_mounting_type` | "Monteringstyp" |
| `field_variation_media` | "Variationsbild" |
| `field_warranty_years` | "Garanti (år)" |

**⚠️ Inkompletta variationstyper (saknar feeds_item)**
`floodlight_variation`, `high_mast_variation`, `street_area_variation`,
`ex_hazardous_variation`, `accessories_variation`

**⚠️ `accessories_variation` är helt tom**
Inga attribut, inga fält — bara core-fält. Behöver definieras.

**⚠️ `field_cri` och `field_lumens` finns på alla Kategori C-variationer**
Men `highbay_variation` har dessa fält via `attribute_watt` + separata fält — inkonsekvent med övriga.

---

## 031-C: Oanvända / inkompletta fält
**Status**: ✅ Uppdaterad med variationsdata

### Produkttyper utan Layout Builder
- `floodlight`, `high_mast`, `street_area`, `ex_hazardous`, `accessories` ❌

### Produkttyper utan feeds_item
- Produkt: `floodlight`, `high_mast`, `street_area`, `ex_hazardous`, `linear_led`, `accessories`
- Variation: `floodlight_variation`, `high_mast_variation`, `street_area_variation`, `ex_hazardous_variation`, `accessories_variation`

### Etikettbrister — produkt (label = machine name)
| Fält | Bör bli |
|---|---|
| `field_dimming` | "Dimming" |
| `field_driver` | "Driver" |
| `field_ip_class` | "IP-klass" |
| `field_mounting_type` | "Monteringstyp" |
| `field_cable` | "Kabel" |
| `field_product_media` (vissa) | "Produktbilder" |
| `field_warranty` | "Garanti (år)" |

### Etikettbrister — variation (label = machine name)
| Fält | Bör bli |
|---|---|
| `field_cri` | "CRI" |
| `field_lumens` | "Lumen" |
| `field_mounting_type` | "Monteringstyp" |
| `field_variation_media` | "Variationsbild" |
| `field_warranty_years` | "Garanti (år)" |

---

## 031-D: Dubblettfält
**Status**: ✅ Alla beslut fattade

| Fält | Beslut | Data | Motivering |
|---|---|---|---|
| `field_product_category` | 🗑️ **Ta bort** | 0 produkter | Exakt dubblett av `field_product_categories`, ingen data |
| `field_product_media_files` | 🗑️ **Ta bort** | 2 produkter (ID 35,36) | Dubblett av `field_product_media`, migrera data först |
| `field_product_type` | ✅ **Behåll** | 12 produkter | Subtyper Kategori A: Base, Emergency, Emergency Daylight, PRO, Sensor |
| `field_producers` | ✅ **Behåll** | 12 produkter | Partner/tillverkare |
| `field_brand` | ✅ **Behåll** | 10 produkter | Varumärke på produkten |

### Fältroller (beslutade 2026-04-15)
- `field_brand` = varumärket på produkten
- `field_producers` = partner/tillverkare som producerar
- `field_product_type` = subtyp inom Kategori A

---

## 031-E: Rensning — plan
**Status**: ⏳ Redo att påbörjas

### Prioriterad rensningslista
1. Ta bort `field_product_category` (0 data)
2. Migrera `field_product_media_files` (ID 35,36) → `field_product_media`, ta bort
3. Rätta etiketter — 7 produktfält + 5 variationsfält
4. Komplettera Kategori C med `feeds_item` + Layout Builder
5. Definiera `accessories_variation` (helt tom)

---

## 031-F: Layout per produkttyp
**Status**: 🔄 Preliminär — verifieras i UI

| Produkttyp | Layout Builder | Kategori | Notering |
|---|---|---|---|
| `led_luminaire_max_opti` | ✅ | A | Konfigurator, hero, media |
| `led_luminaire_srow` | ✅ | A | Konfigurator, hero, media |
| `surge_protection` | ✅ | B | Egna fält |
| `highbay` | ✅ | C | |
| `floodlight` | ❌ | C | Saknar layout |
| `high_mast` | ❌ | C | Saknar layout |
| `street_area` | ❌ | C | Saknar layout |
| `ex_hazardous` | ❌ | C | Saknar layout |
| `linear_led` | ✅ | C | |
| `accessories` | ❌ | C | Saknar layout |

---

## UX-testning (parallellt)

### Testområden
- [ ] Produktsidor — visning, fält, layout
- [ ] Konfiguratorn — flöde, mobilanpassning
- [ ] Cart / offertflöde
- [ ] Navigation & menystruktur
- [ ] Startsidan
- [ ] Sökning & filtrering
- [ ] Språkhantering SV/EN

### Funna UX-brister
*(Fylls i av Stefan)*

---

## Sammanfattning — kritiska fynd

### 🔴 Blockerande
1. **5 produkttyper saknar Layout Builder** — kan inte visas på frontend
2. **6 produkttyper + 5 variationstyper saknar `feeds_item`** — kan inte importeras
3. **Bundle-namn avviker från TASK-029** — `max`/`opti` → `led_luminaire_max_opti`
4. **`accessories_variation` helt tom** — inga fält definierade

### 🟡 Bör åtgärdas
5. **`field_product_category`** — ta bort (dubblett, 0 data)
6. **`field_product_media_files`** — migrera + ta bort
7. **12 fält med machine name som label** — produkt + variation

### 🟢 OK
- Systemstatus ren
- Kategori A välstrukturerade (attribut + JSON-schema)
- Kategori B (surge_protection) komplett med egna fält
- `field_brand`, `field_producers`, `field_product_type` — tydliga syften

---

## JSON API — framtida exponering

### Kategori A — Konfigurator-produkter (variation: attribut via JSON)
Produkt: title, body, field_short_description, field_features, field_series,
field_product_type, field_brand, field_datasheet, field_product_media,
field_hero_media, field_configurator_schema, field_product_categories

### Kategori B — Surge Protection
Produkt: title, field_brand, field_spd_type, field_current_type, field_product_media, field_product_sku
Variation: attribute_pairs, attribute_poles, attribute_voltage_un + alla field_discharge_*, field_protection_level_up, field_voltage_uc

### Kategori C — Externa produkter
Produkt: title, body, field_brand, field_producers, field_datasheet,
field_product_media, field_ip_class, field_dimming, field_driver,
field_mounting_type, field_product_categories, field_product_sku
Variation: attribute_watt, field_lumens, field_cri, field_beam_angle,
field_cct, field_variation_media, field_warranty_years

*(Komplettera när fältstruktur är normaliserad)*

---

## Nästa steg

1. **031-E** — rensning (dubbletter + etiketter)
2. **031-F** — verifiera layouts i UI
3. **Komplettera Kategori C** — feeds_item + Layout Builder
4. **JSON API** — konfigurera exponering per produkttyp

---

## Anteckningar & beslut

- **2026-04-15**: MAX/OPTI/SROW särbehandlas (Kategori A) pga konfigurator + 55k+ varianter
- **2026-04-15**: `field_producers` = partner/tillverkare, `field_brand` = varumärke — olika syften, båda behålls
- **2026-04-15**: `field_product_type` = subtyp för Kategori A (Base/Emergency/PRO/Sensor) — behålls
- **2026-04-15**: Sajten ska exponera öppen JSON för AI-agenter, partners och externa system — fältnamn är API-kontrakt
