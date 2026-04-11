# Task 029: Produkttyper & fältstruktur för utökad produktkatalog

**Created**: 2026-04-11  
**Status**: In Progress  
**Last Updated**: 2026-04-11  
**Related Tasks**: TASK-013 (Attribut-cleanup), TASK-017b (Produktseriesidor)

---

## Bakgrund & kontext

TritonLED expanderar produktkatalogen utöver de egna MAX/OPTI/SROW-produkterna.
Laurits vill kunna presentera och importera produkter från externa leverantörer
(bl.a. från ett tyskt lager — `DE-stock list 26.4.11.xls`) strukturerat via CSV-import.

Referensdokument:
- `docs/03-solutions/product-presentation-template.md` — presentationsmall baserad på TOPPO Nord Line
- `data/DE-stock_list_26_4_11.xls` — källdata för externa produkter

---

## 1. DEFINE

### Mål
Skapa en tydlig och hållbar produkttypsstruktur i Drupal Commerce som:
- Separerar TritonLEDs egna konfigurerbara produkter från externa/importerade
- Möjliggör strukturerad CSV-import per produkttyp
- Återanvänder befintliga fält där möjligt
- Är lätt att underhålla och bygga ut

### Syfte
- Sajten behöver kunna hantera fler produktkategorier än MAX/OPTI/SROW
- Varje produkttyp har egna attribut och CSV-struktur
- Taxonomy kopplar ihop besläktade typer för filtrering och navigation

### Acceptanskriterier
- [ ] Taxonomy "Produktkategori" skapad med rätt termer
- [ ] Alla produkttyper skapade i Drupal Commerce
- [ ] Gemensamma fält återanvända korrekt
- [ ] Typspecifika fält identifierade och skapade
- [ ] CSV-mall dokumenterad per produkttyp
- [ ] Minst en testimport per produkttyp genomförd

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Produkttypsstruktur

#### Taxonomy: `product_category` — "Produktkategori"
Används som tagg på alla produkttyper för gruppering, filtrering och navigation i Views.

| Term (SV) | Term (EN) | Taggade produkttyper |
|-----------|-----------|---------------------|
| Linjär LED-armatur | Linear LED | `max`, `opti`, `srow`, `linear_led` |
| Hallarmatur | Highbay | `highbay` |
| Strålkastare | Floodlight | `floodlight` |
| Mastarmatur | High Mast | `high_mast` |
| Gatu- och områdesarmatur | Street & Area | `street_area` |
| EX-klassad armatur | EX / Hazardous | `ex_hazardous` |
| Överspänningsskydd | Surge Protection | `surge_protection` |
| Tillbehör | Accessories | `accessories` |

#### Commerce Product Types

| Machine name | Namn (SV) | Konfigurator | Notering |
|---|---|---|---|
| `max` | MAX | ✅ | Befintlig — MAX BASE, MAX-PRO, MAX-E, MAX-ED |
| `opti` | OPTI | ✅ | Befintlig |
| `srow` | SROW | ✅ | Befintlig — egen produkttyp |
| `linear_led` | Linjär LED-armatur | ❌ | Ny — Triproof, Linkable Linear, Linear Highbay |
| `highbay` | Hallarmatur | ❌ | Ny — HB-produkter |
| `floodlight` | Strålkastare | ❌ | Ny — FL-produkter, sport/padel |
| `high_mast` | Mastarmatur | ❌ | Ny — HM-produkter |
| `street_area` | Gatu- och områdesarmatur | ❌ | Ny — ST-produkter, pole top |
| `ex_hazardous` | EX-klassad armatur | ❌ | Ny — EX/ATEX-produkter |
| `surge_protection` | Överspänningsskydd | ❌ | Befintlig (IDs 27–34) |
| `accessories` | Tillbehör | ❌ | Ny — sensorer, nödljuspaket |

**Viktigt:** MAX, OPTI och SROW ändras INTE — de är kvar som egna produkttyper.
Taxonomy-taggar används för att gruppera och filtrera tvärs produkttyper.

---

### Fältanalys

#### Kolumner i DE-stock Excel (källdata)
| Kolumn | Drupal-fält | Notering |
|--------|-------------|---------|
| Model | `field_product_sku` / titel | SKU/modellnummer |
| Code | `field_product_code` | Leverantörskod |
| Power | `field_watt` + suffix "W" | Redan finns på MAX/OPTI |
| Lumen | `field_lumen` + suffix "lm" | Ny |
| CCT | `field_cct` + suffix "K" | Redan finns |
| CRI | `field_cri` + prefix ">" | Redan finns |
| Beam | `field_beam_angle` + suffix "°" | Ny |
| Driver | `field_driver` | Ny (MW, Sosen, etc.) |
| Dim | `field_dimming` | Ny (0-10V, DALI, etc.) |
| Cable | `field_cable` | Ny |
| Warranty | `field_warranty` | Ny |
| Standard Price | — | Lagras EJ på frontend |

#### Befintliga fält att återanvända (från MAX/OPTI/SROW)
Dessa fält finns redan och ska återanvändas:
- `field_watt` — effekt i W
- `field_cct` — färgtemperatur i K
- `field_cri` — färgåtergivning
- `field_product_sku` — artikelnummer
- `field_product_media` — produktbilder
- `field_configurator_media` — konfigurator-bilder (endast `configurable_linear`)

#### Nya fält att skapa
| Fältnamn | Typ | Suffix | Används av |
|----------|-----|--------|-----------|
| `field_lumen` | integer | lm | Alla utom surge/accessories |
| `field_beam_angle` | integer | ° | highbay, floodlight, high_mast, street_area |
| `field_driver` | list_string | — | Alla (MW, Sosen, Inventronics…) |
| `field_dimming` | list_string | — | Alla (0-10V, DALI, Casambi, Push…) |
| `field_cable` | string | — | linear_led, ex_hazardous |
| `field_warranty` | integer | år | Alla |
| `field_ip_class` | list_string | — | Alla (IP20, IP23, IP65, IP66, IP69K) |
| `field_product_category` | entity_reference (taxonomy) | — | Alla — kopplar till `product_category` |
| `field_mounting_type` | list_string | — | highbay, floodlight, street_area |

---

### Vald lösning
**Approach**: Config (Commerce product types + Taxonomy + Fields)

Ordning:
1. Skapa taxonomy `product_category` med termer
2. Skapa `field_product_category` som entity_reference-fält
3. Skapa nya gemensamma fält (lumen, beam_angle, driver, dimming, warranty, ip_class, mounting_type)
4. Skapa produkttyper i rätt ordning (börja med `linear_led` som enklast)
5. Tilldela fält per produkttyp
6. Skapa CSV-mallar per produkttyp
7. Testa import

### Motivering
- Återanvänder befintliga fält → mindre underhåll
- Taxonomy-taggning → möjliggör filtrering i Views utan duplicerad logik
- Separata produkttyper → tydliga CSV-mallar, feeds-konfigurationer och view modes per typ
- Suffix-mönster (siffra + enhet) följer befintlig standard från MAX/OPTI

---

## 3. IMPLEMENT

### Fas 1 — Taxonomy & gemensamma fält
*(Ej påbörjad)*

**Steg 1**: Skapa taxonomy `product_category`
```
Admin → Structure → Taxonomy → Add vocabulary
Machine name: product_category
```
Lägg till termer: Linjär LED-armatur, Hallarmatur, Strålkastare, Mastarmatur, Gatu- och områdesarmatur, EX-klassad armatur

**Steg 2**: Skapa gemensamma fält via `drush php:eval`
*(Kommandon dokumenteras här när vi kör dem)*

**Steg 3**: `ddev drush cex -y` → commit

---

### Fas 2 — Produkttyper
*(Ej påbörjad)*

Skapas i denna ordning:
1. `linear_led` — enklast, inga konfigurator-beroenden
2. `highbay`
3. `floodlight`
4. `high_mast`
5. `street_area`
6. `ex_hazardous`
7. `accessories`

---

### Fas 3 — CSV-mallar & feeds
*(Ej påbörjad)*

En feeds-konfiguration + CSV-mall per produkttyp.
CSV-kolumner baseras på fältanalysen ovan.

---

## 4. VERIFY

*(Ej påbörjad)*

---

## 5. COMPLETION

*(Ej påbörjad)*

### Nästa steg efter denna task
- TASK-017b (produktseriesidor) kan byggas ut med nya produkttyper i Views
- Produktpresentationer (se `product-presentation-template.md`) kan kopplas till produktsidorna
