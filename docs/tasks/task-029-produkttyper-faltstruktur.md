# Task 029: Produkttyper & fältstruktur för utökad produktkatalog

**Created**: 2026-04-11  
**Status**: In Progress — Fas 3 återstår  
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

### Acceptanskriterier
- [x] Taxonomy "Produktkategori" skapad med rätt termer
- [x] Alla produkttyper skapade i Drupal Commerce
- [x] Gemensamma fält återanvända korrekt
- [ ] CSV-mall dokumenterad per produkttyp
- [ ] Minst en testimport per produkttyp genomförd

**Godkänt av Stefan**: ✅

---

## 2. PLAN

### Produkttypsstruktur

#### Taxonomy: `product_categories` (machine name MED s)
| Term (EN) | Term (SV) |
|-----------|-----------|
| Linear LED Luminaire | Linjär LED-armatur |
| Highbay | Hallarmatur |
| Floodlight | Strålkastare |
| High Mast | Mastarmatur |
| Street & Area | Gatu- och områdesarmatur |
| EX / Hazardous | EX-klassad armatur |

#### Commerce Product Types — alla skapade ✅
| Machine name | Namn (SV) | Variation type |
|---|---|---|
| `linear_led` | Linjär LED-armatur | `linear_led_variation` |
| `highbay` | Hallarmatur | `highbay_variation` |
| `floodlight` | Strålkastare | `floodlight_variation` |
| `high_mast` | Mastarmatur | `high_mast_variation` |
| `street_area` | Gatu- och områdesarmatur | `street_area_variation` |
| `ex_hazardous` | EX-klassad armatur | `ex_hazardous_variation` |
| `accessories` | Tillbehör | `accessories_variation` |

---

## 3. IMPLEMENT

### Fas 1 — Taxonomy & gemensamma fält ✅ KLAR (2026-04-11)

- Taxonomy `product_categories` skapad med 6 termer + svenska översättningar
- Fält skapade: `field_lumen`, `field_beam_angle`, `field_warranty`, `field_cable`, `field_driver`, `field_dimming`, `field_ip_class`, `field_mounting_type`, `field_product_category`
- `field_product_category` tilldelad på: max, opti, srow, surge_protection
- Committad: `[TASK-029] Fas 1`

---

### Fas 2 — Produkttyper ✅ KLAR (2026-04-11)

Alla 7 produkttyper skapade via `vendor/bin/drush php:eval` (inuti `ddev ssh`).

**Viktiga lärdomar:**
- Fälten återanvändes från `default` variation bundle — rätt namn är `field_lumens` och `field_warranty_years` (inte `field_lumen`/`field_warranty`)
- `drush php:eval` körs inuti `ddev ssh` — ALDRIG `php` direkt
- Alla variation types har `orderItemType: quote` och `generateTitle: true`

**Fält per typ (variation):** `field_lumens`, `field_warranty_years`, `field_cri`, `field_variation_media`  
+ `field_mounting_type` på: highbay, floodlight, street_area

**Fält per typ (produkt):** `field_product_category`, `field_product_media`, `field_ip_class`, `field_driver`, `field_dimming`  
+ `field_cable` på: linear_led, ex_hazardous

Commits: `[TASK-029] Fas 2: [typ] product type + variation type skapad, fält tilldelade` × 7

---

### Fas 3 — CSV-mallar & feeds
*(Nästa session)*

En feeds-konfiguration + CSV-mall per produkttyp.
Börja med `linear_led` — enklast och mest data finns i DE-stock Excel.

**Ordning per produkttyp:**
1. Skapa CSV-mall baserad på fältöversikten i SKILL.md
2. Skapa feed type för produkter (`tritonled_[typ]_products`)
3. Skapa feed type för variationer (`tritonled_[typ]_variations`)
4. Testimport med 1–2 produkter
5. Verifiera i admin → commit

**Starta med:** `linear_led` — hämta data från `data/DE-stock_list_26_4_11.xls`

---

## 4. VERIFY

*(Ej påbörjad — efter Fas 3)*

---

## 5. COMPLETION

*(Ej påbörjad)*

### Nästa steg efter denna task
- TASK-017b (produktseriesidor) kan byggas ut med nya produkttyper i Views
- Produktpresentationer (se `product-presentation-template.md`) kan kopplas till produktsidorna
