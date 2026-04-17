# Task 031: Site Audit — Fält, struktur & UX-beredskap

**Created**: 2026-04-15
**Status**: In Progress
**Last Updated**: 2026-04-17
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
- `field_product_media` formatter visat bara 1 bild per default — måste sättas till obegränsat i Layout Builder per produkttyp
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
| `field_product_media` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
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

## 031-C: Oanvända / inkompletta fält
**Status**: ✅ Uppdaterad

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
**Status**: ✅ Alla beslut fattade och åtgärdade

| Fält | Beslut | Status |
|---|---|---|
| `field_product_category` | 🗑️ Ta bort | ✅ Borttaget från alla 10 bundles |
| `field_product_media_files` | 🗑️ Ta bort | ✅ Data migrerad till `field_product_media`, fält borttaget |
| `field_product_type` | ✅ Behåll | Subtyper Kategori A |
| `field_producers` | ✅ Behåll | Partner/tillverkare |
| `field_brand` | ✅ Behåll | Varumärke på produkten |

---

## 031-E: Rensning
**Status**: 🔄 Delvis klar

### Gjort
- ✅ `field_product_category` borttaget från alla bundles
- ✅ `field_product_media_files` data migrerad (produkt 35: 2 bilder, produkt 36: 1 bild)
- ✅ Dubbletter i `field_product_media` rensade
- ✅ Layout Builder reset på produkt 35 (refererade borttaget fält)
- ✅ Produkt 35 (DB53 Hilton linear_led) — layout fungerar (2026-04-17)

### Återstår
- [ ] Sätt `field_product_media` formatter till obegränsat antal bilder i Layout Builder per produkttyp
- [ ] Komplettera Kategori C med `feeds_item` + Layout Builder
- [ ] Definiera `accessories_variation` (helt tom)

---

## 031-F: Layout per produkttyp
**Status**: 🔄 Delvis klar

| Produkttyp | Layout Builder | Kategori | Notering |
|---|---|---|---|
| `led_luminaire_max_opti` | ✅ | A | Konfigurator, hero, media |
| `led_luminaire_srow` | ✅ | A | Konfigurator, hero, media |
| `surge_protection` | ✅ | B | Egna fält |
| `highbay` | ✅ | C | field_product_media formatter — sätt till obegränsat |
| `floodlight` | ❌ | C | Saknar layout |
| `high_mast` | ❌ | C | Saknar layout — YAML skapad, ej verifierad |
| `street_area` | ❌ | C | Saknar layout — YAML skapad, ej verifierad |
| `ex_hazardous` | ❌ | C | Saknar layout |
| `linear_led` | ✅ | C | Klar (2026-04-17) |
| `accessories` | ❌ | C | Saknar layout |

---

## 031-G: Översättningar (SV/EN)
**Status**: 🔄 Känd brist — återkommer till detta

### Beteende
Inkonsekvent — ibland fungerar fältöversättningar direkt, ibland måste
translation aktiveras manuellt per fält på bundle-nivå.

### Workaround
Aktivera translation per fält under:
`/admin/config/regional/content-language`
→ Välj entity type → bundle → aktivera önskade fält

### Påverkade fält
- `body` — saknas på vissa produkttyper, vilket hindrade nodöversättningar
- Övriga fält okontrollerade

### Notering
Prioritet: låg just nu. Återkommer när Kategori C är komplett.

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
1. **4 produkttyper saknar Layout Builder** — `floodlight`, `ex_hazardous`, `accessories` + `high_mast`/`street_area` (YAML ej verifierad)
2. **6 produkttyper + 5 variationstyper saknar `feeds_item`** — kan inte importeras
3. **`accessories_variation` helt tom** — inga fält definierade

### 🟡 Bör åtgärdas
4. **12 fält med machine name som label** — produkt + variation
5. **`field_product_media` formatter** — sätt till obegränsat per produkttyp i Layout Builder
6. **Översättningar inkonsekvent** — aktivera manuellt per bundle vid behov

### 🟢 Åtgärdat
- `field_product_category` borttaget
- `field_product_media_files` migrerad och borttagen
- Dubbletter i media rensade
- Produkt 35 (DB53 Hilton) klar

---

## JSON API — framtida exponering

### Kategori A — Konfigurator-produkter
Produkt: title, body, field_short_description, field_features, field_series,
field_product_type, field_brand, field_datasheet, field_product_media,
field_hero_media, field_configurator_schema, field_product_categories

### Kategori B — Surge Protection
Produkt: title, field_brand, field_spd_type, field_current_type, field_product_media, field_product_sku
Variation: attribute_pairs, attribute_poles, attribute_voltage_un + field_discharge_*, field_protection_level_up, field_voltage_uc

### Kategori C — Externa produkter
Produkt: title, body, field_brand, field_producers, field_datasheet,
field_product_media, field_ip_class, field_dimming, field_driver,
field_mounting_type, field_product_categories, field_product_sku
Variation: attribute_watt, field_lumens, field_cri, field_beam_angle,
field_cct, field_variation_media, field_warranty_years

---

## Nästa steg

1. Verifiera `high_mast` + `street_area` YAML via `cim` + UI
2. Bygg Layout Builder manuellt för `floodlight`, `ex_hazardous`, `accessories`
3. Komplettera Kategori C — `feeds_item` på produkt + variation
4. Sätt `field_product_media` formatter till obegränsat i Layout Builder
5. Definiera `accessories_variation` fält
6. Rätta etiketter (12 fält) om ej gjort
7. JSON API — konfigurera exponering per produkttyp
8. Översättningar — återkommer när Kategori C är komplett

---

## Anteckningar & beslut

- ✅ Config-synk löst — 79 otrackade filer från prod-synk committade (2026-04-15)
- ✅ Fältetiketter uppdaterade via YAML — 62 field.field-filer (EN) + cim (2026-04-15)
- ✅ `field_product_category` + `field_product_media_files` borttagna från DB och config/sync (2026-04-15)
- ✅ Produkt 35 (DB53 Hilton linear_led) layout klar (2026-04-17)
- **2026-04-15**: Fältetiketter satta till engelska via YAML direkt (inte admin UI) — snabbare och säkrare. Svenska etiketter hanteras via interface translation i DB.
- **2026-04-15**: `field_producers` = partner/tillverkare, `field_brand` = varumärke — olika syften, båda behålls
- **2026-04-15**: `field_product_type` = subtyp för Kategori A (Base/Emergency/PRO/Sensor) — behålls
- **2026-04-15**: Sajten ska exponera öppen JSON för AI-agenter, partners och externa system — fältnamn är API-kontrakt
- **2026-04-15**: `field_product_media` formatter måste sättas till obegränsat antal bilder per produkttyp i Layout Builder
- **2026-04-17**: Översättningar inkonsekvent — workaround: aktivera manuellt per fält i `/admin/config/regional/content-language`. Låg prioritet tills vidare.
