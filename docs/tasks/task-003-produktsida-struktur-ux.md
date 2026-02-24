# Task: Produktsida — Struktur, Priser & UX

**Status**: Aktiv  
**Prioritet**: Hög  
**Skapad**: 2026-02-23  
**Uppdaterad**: 2026-02-24

---

## Bakgrund

Triton LED är ett B2B-system med quote-baserad försäljning. Priser ska inte visas för anonyma besökare — istället ska offertflödet vara primärt. Priser behöver däremot vara tillgängliga för inloggade partners på olika nivåer.

**Kärnan i systemet**: En "Master" CSV-fil som körs varje midnatt via cron för att importera nya produkter eller uppdatera befintliga (inklusive priser). Alla fält på produkten måste vara definierade utifrån vad denna CSV-fil ska innehålla.

---

## Rollstruktur (beslutad 2026-02-24)

| Roll | Ser listpris | Rabatt | Offertflöde |
|------|-------------|--------|-------------|
| Anonymous | ❌ | ❌ | ✅ "Request Quote" |
| Elektriker | ✅ | ❌ | ✅ |
| Partner Silver | ✅ | ✅ nivå 1 | ✅ |
| Partner Gold | ✅ | ✅ nivå 2 | ✅ |
| Admin | ✅ | ✅ | ✅ |

**Prisdöljning**: Renderas INTE för anonymous (ej CSS-hide) — säkerhetsaspekt.  
**Rabatter**: Ett listpris i CSV. Rabatter hanteras i Commerce Price Lists per roll eller individuellt per användare. KISS-modellen.

---

## Master CSV-struktur (beslutad 2026-02-24)

### Princip
- En rad per variant, produktdata upprepas
- Unik nyckel produkt: `product_sku` (t.ex. `COMET-HB`)
- Unik nyckel variant: `sku` (t.ex. `COMET-HB-40W-4000K`)
- Varianten kopplas till produkten via `product_sku` (ej titel)
- Ett pris (listpris SEK) — rabatter hanteras i Commerce
- Bilder/video/PDF hanteras manuellt i Drupal (ej i CSV)
- EAN utelämnas tills vidare (framtida behov)

### Produktnivå (upprepas per rad)

| Kolumn | Fält | Kommentar |
|--------|------|-----------|
| `product_sku` | field_product_sku | Unik nyckel (**NY** — saknas idag) |
| `product_title` | title | Produktnamn |
| `product_brand` | field_brand | Varumärke |
| `product_series` | field_series | Serie |
| `product_short_description` | field_short_description | Kort beskrivning |
| `product_body` | body | Lång beskrivning |
| `product_features` | field_features | Pipe-separerade (`Feature 1\|Feature 2`) |
| `product_status` | status | `1` = publicerad |
| `store_id` | stores | Butiksnamn |

### Variantnivå (unik per rad)

| Kolumn | Fält | Kommentar |
|--------|------|-----------|
| `sku` | sku | Unik nyckel för varianten |
| `price` | price | Listpris SEK |
| `variation_status` | status | `1` = aktiv |

### Attribut (variantnivå)

| Kolumn | Kommentar |
|--------|-----------|
| `attribute_watt` | |
| `attribute_cct` | |
| `attribute_ip_rating` | |
| `attribute_beam_angle` | |
| `attribute_color` | |
| `attribute_length` | |
| `attribute_voltage` | |
| `attribute_accessories` | |

### Tekniska specifikationer (variantnivå)

| Kolumn |
|--------|
| `field_lumens` |
| `field_efficacy` |
| `field_cri` |
| `field_power_factor` |
| `field_current` |
| `field_frequency` |
| `field_dimmable` |
| `field_dimming_protocol` |
| `field_control_system` |
| `field_rated_life` |
| `field_warranty_years` |
| `field_energy_class` |
| `field_material` |
| `field_housing_color` |
| `field_mounting_type` |
| `field_weight` |
| `field_dimension_length` |
| `field_dimension_width` |
| `field_dimension_height` |
| `field_operating_temp_min` |
| `field_operating_temp_max` |
| `field_ambient_temp` |
| `field_case_temp` |
| `field_ik_rating` |
| `field_rohs` |
| `field_ce_marking` |
| `field_enec` |
| `field_installation_notes` |

---

## Subtasks

### ST-1: Skapa field_product_sku på Commerce-produkten ← STARTA HÄR
**Mål**: Ersätt title-referensen med ett robust produkt-ID.

- [ ] Skapa textfält `field_product_sku` på commerce_product (default)
  - Label: "Product SKU"
  - Maskinfält: `field_product_sku`
  - Typ: Text (plain), max 64 tecken
  - Required: Ja
- [ ] Uppdatera Feeds-mappning `tritonled_products`: lägg till product_sku → field_product_sku (unik nyckel)
- [ ] Uppdatera Feeds-mappning `tritonled_variations`: byt `reference_by: title` till `reference_by: field_product_sku`
- [ ] Config export: `ddev drush cex -y`

**Verktyg**: Admin UI + config export  
**Kräver kod?**: NEJ

**Not om dummyprodukter**: Befintliga dummyprodukter berörs inte. När riktiga produkter importeras skapas nya entiteter. Dummyprodukter raderas manuellt via VBO när riktiga finns.

---

### ST-2: Skapa test-CSV och verifiera import
**Mål**: Bekräfta att Master CSV-strukturen fungerar end-to-end.

- [ ] Skapa `data/import/master-test.csv` med 2 produkter × 2 varianter
- [ ] Importera via Admin → Content → Feeds (Products)
- [ ] Importera via Admin → Content → Feeds (Variations)
- [ ] Verifiera produkter och varianter skapades korrekt
- [ ] Re-importera — verifiera att inget dupliceras
- [ ] Verifiera att product_sku-referensen fungerar (ej titel)
- [ ] Config export + dokumentera

---

### ST-3: Rollstruktur i Drupal
*(Efter ST-1-2)*

- [ ] Skapa roller: Elektriker, Partner Silver, Partner Gold
- [ ] Konfigurera permissions per roll
- [ ] Prisdöljning för anonymous (renderas ej)
- [ ] Commerce Price Lists per roll

---

### ST-4: Fältstruktur & permissions
*(Efter ST-3)*

- [ ] Verifiera befintliga fält mot Master CSV
- [ ] field_permissions per roll vid behov

---

### ST-5: Cron-import (midnatt)
*(Efter ST-2)*

- [ ] Konfigurera Feeds för automatisk körning varje midnatt
- [ ] Testa cron-körning
- [ ] Feeds Item-bugg: rensa feeds_item efter import (se 03-solutions/feeds-item-ajax-bug.md)

---

### ST-6: Produktsidans UX
*(När ST-1–ST-4 är klara)*

- [ ] Layout tekniska specifikationer
- [ ] CTA per roll
- [ ] Dokumentsektion
- [ ] Responsiv design

---

### ST-7: Offertflöde — styling
*(Redan byggt, stylas sist)*

---

## Tekniska noter

- `field_product_sku` saknas idag — måste skapas innan CSV-test
- Befintlig variations-import refererar produkt via `title` — måste bytas till `field_product_sku`
- Commerce Price Lists för rabatter per roll (ej i CSV)
- EAN utelämnas tills kund levererar produktlista med EAN
- feeds_item-bugg: måste rensas på varianter efter varje import

---

## Startordning

**ST-1 → ST-2 → ST-3 → ST-4 → ST-5 → ST-6 → ST-7**
