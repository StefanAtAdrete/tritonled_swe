# TASK-003: Commerce Feeds CSV Import

**Created**: 2026-02-17
**Status**: Not Started
**Last Updated**: 2026-02-17
**Related Tasks**: TASK-001, TASK-002

---

## 1. DEFINE

### Mål
Skapa ett CSV-baserat importflöde för Commerce produkter och varianter
via feeds + commerce_feeds. CSV-filen ska kunna underhållas i Excel och
importeras manuellt eller automatiskt (cron).

### Syfte
- Ge projektet riktig produktdata att arbeta med
- Möjliggöra strukturerad produkthantering utanför Drupal
- Förbereda för automatiserad import (2x dagligen)

### Flöden
- **In**: CSV → Drupal Commerce (produkter + varianter)
- **Ut**: JSON → Partners (separat task)

### Moduler (redan installerade)
- `feeds` — grundmodul för import
- `commerce_feeds` — Commerce-integration
- `feeds_tamper` — datatransformation vid behov

### Acceptanskriterier
- [ ] feeds + commerce_feeds aktiverade
- [ ] CSV-struktur definierad och dokumenterad
- [ ] Feed type konfigurerad med mappning produkt → fält
- [ ] Feed type konfigurerad med mappning variant → fält
- [ ] Testimport med minst 2 produkter + varianter lyckas
- [ ] Befintliga produkter uppdateras (ej dupliceras) vid re-import
- [ ] Config exporterad

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. CSV-STRUKTUR

### Princip
Commerce Feeds hanterar produkter och varianter som **separata imports**
eller som en **platt CSV** där produktdata upprepas per variant-rad.

**Valt approach**: Platt CSV — en rad per variant, produktdata upprepas.
Enklare att hantera i Excel, feeds_tamper kan deduplera vid behov.

### Unik nyckel
- **Produkt**: `product_sku` (t.ex. `COMET-HB`)
- **Variant**: `sku` (t.ex. `COMET-HB-40W-4000K-DALI`)

Feeds använder SKU som unik nyckel för att uppdatera utan att duplicera.

---

### CSV-kolumner

#### Produktnivå (upprepas per variant-rad)
| Kolumn | Fält | Exempel |
|--------|------|---------|
| `product_sku` | Produkt-ID (unik nyckel) | `COMET-HB` |
| `product_title` | title | `Comet LED Highbay` |
| `product_brand` | field_brand | `TritonLED` |
| `product_series` | field_series | `Comet` |
| `product_short_description` | field_short_description | `Industrial LED highbay...` |
| `product_body` | body | `Längre beskrivning...` |
| `product_features` | field_features | `Feature 1\|Feature 2` |
| `product_status` | status | `1` (published) |

#### Variantnivå (unik per rad)
| Kolumn | Fält | Exempel |
|--------|------|---------|
| `sku` | SKU (unik nyckel) | `COMET-HB-40W-4000K-DALI` |
| `price` | price | `1200.00` |
| `currency` | price currency | `SEK` |
| `attribute_watt` | attribute_watt | `40` |
| `attribute_cct` | attribute_cct | `4000` |
| `attribute_ip_rating` | attribute_ip_rating | `IP65` |
| `attribute_beam_angle` | attribute_beam_angle | `120` |
| `attribute_color` | attribute_color | `Silver` |
| `attribute_length` | attribute_length | `600` |
| `attribute_voltage` | attribute_voltage | `220-240V` |
| `attribute_accessories` | attribute_accessories | `Driver included` |

#### Tekniska specifikationer (variant)
| Kolumn | Fält | Exempel |
|--------|------|---------|
| `field_lumens` | field_lumens | `4800` |
| `field_efficacy` | field_efficacy | `120` |
| `field_cri` | field_cri | `80` |
| `field_power_factor` | field_power_factor | `0.95` |
| `field_current` | field_current | `0.18` |
| `field_frequency` | field_frequency | `50-60` |
| `field_dimmable` | field_dimmable | `1` |
| `field_dimming_protocol` | field_dimming_protocol | `DALI` |
| `field_control_system` | field_control_system | `DALI 2.0` |
| `field_rated_life` | field_rated_life | `50000` |
| `field_warranty_years` | field_warranty_years | `5` |
| `field_energy_class` | field_energy_class | `A` |
| `field_material` | field_material | `Aluminium` |
| `field_housing_color` | field_housing_color | `Silver` |
| `field_mounting_type` | field_mounting_type | `Pendant` |
| `field_weight` | field_weight | `2.5` |
| `field_dimension_length` | field_dimension_length | `600` |
| `field_dimension_width` | field_dimension_width | `200` |
| `field_dimension_height` | field_dimension_height | `150` |
| `field_operating_temp_min` | field_operating_temp_min | `-20` |
| `field_operating_temp_max` | field_operating_temp_max | `45` |
| `field_ambient_temp` | field_ambient_temp | `25` |
| `field_case_temp` | field_case_temp | `60` |
| `field_ik_rating` | field_ik_rating | `IK08` |
| `field_rohs` | field_rohs | `1` |
| `field_ce_marking` | field_ce_marking | `1` |
| `field_enec` | field_enec | `0` |
| `field_installation_notes` | field_installation_notes | `Min. mounting height 5m` |
| `variation_status` | status | `1` |

#### Bilder (hanteras separat — se not nedan)
| Kolumn | Fält | Exempel |
|--------|------|---------|
| `image_url` | field_variation_images | `https://cdn.../comet-hb.jpg` |

---

### Not om priser
Priset ska finnas i systemet men **aldrig renderas på tritonled.se**.
- Ej dolt via CSS — utan bokstavligen inte renderat (ej i view modes för frontend)
- Partners med API-access får listpriset via JSON-export
- Partners kan själva välja om de vill visa det
- Åtkomstnivåer avgör vad som exponeras

Detta påverkar:
- View modes: `price`-fältet läggs INTE till i någon frontend view mode
- JSON-export (TASK-xxx): priset inkluderas för partner-API
- Commerce: priset lagras i databasen som vanligt

### Not om bilder
Bilder via URL-import kräver `feeds_ex_fetcher` eller liknande.
Bilder hanteras i en **separat sub-task** — fokus nu på textdata och priser.

---

## 3. SUB-TASKS (i ordning)

### SUB-TASK A: Aktivera moduler
- `ddev drush en feeds commerce_feeds feeds_tamper -y`
- Verifiera i admin

### SUB-TASK B: Skapa exempel-CSV
- Skapa `data/import/products-example.csv` med 2 produkter × 2 varianter
- Följer strukturen ovan

### SUB-TASK C: Konfigurera Feed type — Produkter
- Admin → Structure → Feed types → Add
- Fetcher: Upload (manuell) eller HTTP (URL)
- Parser: CSV
- Processor: Commerce Product
- Mappning: product_sku → SKU (unik nyckel), title, fält...

### SUB-TASK D: Konfigurera Feed type — Varianter
- Separat feed type för varianter
- Processor: Commerce Product Variation
- Mappning: sku → SKU (unik nyckel), attribut, specs...

### SUB-TASK E: Testa import
- Importera exempel-CSV
- Verifiera produkter + varianter skapades korrekt
- Re-importera — verifiera att inget dupliceras

### SUB-TASK F: Config export + dokumentation
- `ddev drush cex -y`
- Dokumentera i `03-solutions/`

---

## 4. PLAN

**Beslutsträd**: `/docs/DRUPAL-DECISION-TREE.md`  
**Ordning**: A → B → C → D → E → F  
**Kräver kod?**: Troligen NEJ — feeds + commerce_feeds hanterar allt via UI

**Godkänt av Stefan**: ⏳ Väntar

---

## 5. IMPLEMENT + VERIFY

*(Fylls i nästa session)*

---

**Version**: 1.0
**Skapad**: 2026-02-17
**Författare**: Claude + Stefan
