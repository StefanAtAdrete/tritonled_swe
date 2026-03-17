# Task 020: Produktarkitektur Rebuild

**Created**: 2026-03-17  
**Status**: Not Started  
**Last Updated**: 2026-03-17  
**Related Tasks**: TASK-013, TASK-015

---

## 1. DEFINE

### Mål
Bygga om Commerce-produktstrukturen från grunden med korrekta Product Types, attribut och konfigurator-scheman — baserat på verifierad data från triton-solutions.co.

### Syfte
Befintlig produktdata är felaktig: saknar optik, färg, drivdon, CRI Ra90, CCT 5000K/6500K. SKU-formatet stämmer inte med Tritons schema. SROW är fundamentalt annorlunda från MAX/OPTI men behandlas likadant idag. Konfiguratorn (TASK-015) kan inte byggas förrän datastrukturen är rätt.

### Acceptanskriterier
- [ ] Två Commerce Product Types skapade: `led_luminaire_max_opti` och `led_luminaire_srow`
- [ ] Alla attribut enligt schemat finns på rätt Product Type
- [ ] `field_configurator_schema` (Long text) finns på båda typerna
- [ ] MAX BASE-produkt importerad med komplett variantdata (alla kombinationer)
- [ ] SKU:er matchar Tritons schema: `M-A0C8-J19N1`-format
- [ ] Befintliga felaktiga produkter/varianter borttagna
- [ ] Feeds-instanser per modell (börja med MAX BASE)

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`  
**Steg**: Config → Commerce Product Types och Attributes via admin UI → CSV/Feeds import

### Vald lösning
**Approach**: Config + Feeds import  
**Specifik lösning**: Skapa Product Types och attribut via Drupal admin UI (→ cex), generera korrekta CSV:er från befintliga scheman i `/docs/product-schemas/`, importera via Feeds.

### Motivering
Befintlig data är inte värd att migrera — det är snabbare att bygga rent. Scheman finns redan färdiga i JSON-form och kan genereras till CSV direkt.

### Alternativ övervägda
1. **Migrera befintliga produkter**: Lägga till saknade attribut på befintliga varianter — avfärdat, för många felaktiga kombinationer och fel SKU-format.
2. **En gemensam Product Type**: Avfärdat — SROW har `chips`/`ip_class` istället för `cri`/`sensor`, helt annan watt-logik och andra optiker.

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

### Sub-tasks

| Sub-task | Beskrivning | Status |
|----------|-------------|--------|
| TASK-020-01 | Skapa Product Types + attribut | ⬜ Not Started |
| TASK-020-02 | Lägg till field_configurator_schema | ⬜ Not Started |
| TASK-020-03 | Radera befintliga felaktiga produkter | ⬜ Not Started |
| TASK-020-04 | Generera MAX BASE variations CSV | ⬜ Not Started |
| TASK-020-05 | Feeds-instans + import MAX BASE | ⬜ Not Started |
| TASK-020-06 | Verifiera SKU:er och varianter | ⬜ Not Started |
| TASK-020-07 | Upprepa för övriga modeller (prioritetsordning nedan) | ⬜ Not Started |

### Prioritetsordning för modellimport
1. MAX BASE (proof-of-concept, enklast)
2. OPTI BASE (verifiera att samma flöde fungerar)
3. SROW BASE (verifiera SROW Product Type)
4. Emergency-modeller (MAX-E, MAX-ED, OPTI-E, OPTI-ED, SROW-E, SROW-ED)
5. Sensor-modeller (MAX-S, OPTI-S)
6. MAX PRO

---

### TASK-020-01: Skapa Product Types + attribut

**Product Type: `led_luminaire_max_opti`**

Attribut (i korrekt ordning för konfiguratorn):
```
attribute_length   → Längd        (text list)
attribute_driver   → Drivdon      (text list)
attribute_endcap   → Ändstycke    (text list)
attribute_cri      → CRI          (text list)
attribute_sensor   → Sensor       (text list) ← används av -S och -E/ED
attribute_kelvin   → Kelvin (CCT) (text list)
attribute_watt     → Effekt (W)   (text list)
attribute_optic    → Optik        (text list)
attribute_color    → Färg         (text list)
```

**Product Type: `led_luminaire_srow`**

Attribut:
```
attribute_length   → Längd        (text list)
attribute_driver   → Drivdon      (text list)
attribute_endcap   → Ändstycke    (text list)
attribute_chips    → Chips/Modul  (text list) ← SROW-specifikt
attribute_ip_class → IP-klass     (text list) ← SROW-specifikt
attribute_kelvin   → Kelvin (CCT) (text list)
attribute_watt     → Effekt (W)   (text list)
attribute_optic    → Optik        (text list)
attribute_color    → Färg         (text list)
```

**Drush-kommandon att köra efter UI-konfiguration:**
```bash
ddev drush cex -y
git add -A
git commit -m "[TASK-020-01] Add led_luminaire_max_opti and led_luminaire_srow product types with attributes"
```

---

### TASK-020-02: Lägg till field_configurator_schema

Lägg till fältet på båda Product Types via Drupal admin:
- Fälttyp: **Text (plain, long)**
- Maskinnamn: `field_configurator_schema`
- Label: `Konfigurator-schema (JSON)`
- Rows: 20

```bash
ddev drush cex -y
git commit -m "[TASK-020-02] Add field_configurator_schema to product types"
```

---

### TASK-020-03: Radera befintliga felaktiga produkter

Via Drupal admin → Commerce → Products:
- Radera alla befintliga TRITON-MAX, TRITON-OPTI, TRITON-SROW-produkter
- Radera tillhörande varianter

OBS: Kontrollera att inga orders refererar till dessa produkter innan radering.

```bash
# Alternativt via Drush:
ddev drush php:eval "
\$products = \Drupal::entityTypeManager()->getStorage('commerce_product')->loadMultiple();
foreach (\$products as \$p) { \$p->delete(); }
echo 'Deleted ' . count(\$products) . ' products';
"
```

---

### TASK-020-04: Generera MAX BASE variations CSV

CSV-strukturen baseras på schemat i `/docs/product-schemas/max-configurator-schemas.json` (slug: `max-base`).

Fält i CSV:
```
sku, product_sku, product_title, price, currency,
attribute_length, attribute_driver, attribute_endcap, attribute_cri,
attribute_kelvin, attribute_watt, attribute_optic, attribute_color,
field_lumens, field_efficacy, field_current,
variation_status, stores
```

SKU-format: `M-{length}{driver}{endcap}{cri}{kelvin}{watt}{optic}{color}`

Exempel: `M-A0CJ19N1` = 0,5m + On/Off + Cable Gland + Ra80 + 3000K + 19W + 30°Narrow + Anodized Grey

**Antal varianter MAX BASE (beräknat):**
- 4 längder × 3 drivdon × (3-5 ändstycken, beror på drivdon) × 2 CRI × 4 CCT × (3 watt per längd) × 5 optik × 3 färg
- Ca 1 500–2 000 varianter (dependsOn reducerar från teoretiska max)

Filen sparas som: `/private/feeds/max-base-variations.csv`

---

### TASK-020-05: Feeds-instans + import MAX BASE

1. Skapa Products-feed för MAX BASE (en produkt-rad med schema-JSON i `field_configurator_schema`)
2. Skapa Variations-feed för MAX BASE
3. Kör products-feed FÖRST
4. Kör variations-feed
5. Rensa feeds_item (se lärdomar från tidigare)

```bash
ddev drush feeds:import max_base_products --no-interaction
ddev drush feeds:import max_base_variations --no-interaction
```

---

### TASK-020-06: Verifiera SKU:er och varianter

```bash
# Räkna varianter
ddev drush php:eval "
\$count = \Drupal::entityTypeManager()
  ->getStorage('commerce_product_variation')
  ->getQuery()->accessCheck(FALSE)->count()->execute();
echo 'Total variations: ' . \$count;
"

# Verifiera specifik SKU
ddev drush php:eval "
\$v = \Drupal::entityTypeManager()
  ->getStorage('commerce_product_variation')
  ->loadByProperties(['sku' => 'M-A0CJ19N1']);
print_r(array_keys(\$v));
"
```

---

## 4. VERIFY

### Acceptanskriterier att kontrollera
- [ ] Två Product Types finns i admin
- [ ] `field_configurator_schema` finns och kan redigeras på produkten
- [ ] MAX BASE-produkt har schema-JSON ifyllt
- [ ] Varianter har korrekta SKU:er i M-format
- [ ] JSON:API returnerar varianter: `/jsonapi/commerce_product_variation/default?filter[sku][value]=M-A0CJ19N1`
- [ ] Inga felaktiga gamla produkter kvar

---

## 5. COMPLETION

### Status: ⬜ Not Started

### Beroenden
- Arkitekturdokument: `/docs/tasks/task-015-konfigurator-arkitektur.md`
- Scheman: `/docs/product-schemas/`
- Nästa task efter denna: TASK-015 (konfigurator-implementation)

### Filer att referera
- `/docs/product-schemas/max-configurator-schemas.json`
- `/docs/product-schemas/opti-configurator-schemas.json`
- `/docs/product-schemas/srow-configurator-schemas.json`
- `/docs/product-schemas/README.md`
- `/docs/tasks/task-015-konfigurator-arkitektur.md`
