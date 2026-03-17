# Task 020: Produktarkitektur Rebuild

**Created**: 2026-03-17  
**Status**: In Progress  
**Last Updated**: 2026-03-17  
**Related Tasks**: TASK-013, TASK-015

---

## 1. DEFINE

### Mål
Bygga om Commerce-produktstrukturen från grunden med korrekta Product Types, attribut och konfigurator-scheman — baserat på verifierad data från triton-solutions.co.

### Syfte
Befintlig produktdata är felaktig: saknar optik, färg, drivdon, CRI Ra90, CCT 5000K/6500K. SKU-formatet stämmer inte med Tritons schema. SROW är fundamentalt annorlunda från MAX/OPTI men behandlas likadant idag. Konfiguratorn (TASK-015) kan inte byggas förrän datastrukturen är rätt.

### Acceptanskriterier
- [x] Två Commerce Product Types skapade: `led_luminaire_max_opti` och `led_luminaire_srow`
- [x] Alla attribut enligt schemat finns på rätt Product Type
- [x] `field_configurator_schema` (Long text) finns på båda typerna
- [ ] MAX BASE-produkt importerad med komplett variantdata (alla kombinationer)
- [ ] SKU:er matchar Tritons schema: `M-A0C8-J19N1`-format
- [ ] Befintliga felaktiga produkter/varianter borttagna
- [ ] Feeds-instanser per modell (börja med MAX BASE)

**Godkänt av Stefan**: ✅ Godkänd (implicit — byggordningen godkänd 2026-03-17)

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`  
**Steg**: Config → Commerce Product Types och Attributes via Drush → CSV/Feeds import

### Vald lösning
**Approach**: Config + Feeds import  
**Specifik lösning**: Skapa Product Types och attribut via Drush php:eval (→ cex), generera korrekta CSV:er från befintliga scheman i `/docs/product-schemas/`, importera via Feeds.

### Motivering
Befintlig data är inte värd att migrera — det är snabbare att bygga rent. Scheman finns redan färdiga i JSON-form och kan genereras till CSV direkt.

### Alternativ övervägda
1. **Migrera befintliga produkter**: Lägga till saknade attribut på befintliga varianter — avfärdat, för många felaktiga kombinationer och fel SKU-format.
2. **En gemensam Product Type**: Avfärdat — SROW har `chips`/`ip_class` istället för `cri`/`sensor`, helt annan watt-logik och andra optiker.

**Godkänt av Stefan**: ✅ Godkänd

---

## 3. IMPLEMENT

### Sub-tasks

| Sub-task | Beskrivning | Status |
|----------|-------------|--------|
| TASK-020-01 | Skapa Product Types + attribut | ✅ Klar |
| TASK-020-02 | Lägg till field_configurator_schema | ✅ Klar |
| TASK-020-03 | Radera befintliga felaktiga produkter | ⬜ Not Started |
| TASK-020-04 | Generera MAX BASE variations CSV | ⬜ Not Started |
| TASK-020-05 | Feeds-instans + import MAX BASE | ⬜ Not Started |
| TASK-020-06 | Verifiera SKU:er och varianter | ⬜ Not Started |
| TASK-020-07 | Upprepa för övriga modeller | ⬜ Not Started |

---

### TASK-020-01 ✅ Klar — 2026-03-17

**Vad gjordes:**
- 6 nya Commerce-attribut skapade via Drush: `kelvin`, `endcap`, `optic`, `sensor`, `chips`, `ip_class`
- Variation Type `led_luminaire_max_opti` skapad med attribut: length, driver, endcap, cri, sensor, kelvin, watt, optic, color
- Variation Type `led_luminaire_srow` skapad med attribut: length, driver, endcap, chips, ip_class, kelvin, watt, optic, color
- Product Type `led_luminaire_max_opti` skapad (kopplad till variation type max_opti)
- Product Type `led_luminaire_srow` skapad (kopplad till variation type srow)

**Hinder/Problem:**
- `purchasable_entity_shippable` trait finns inte installerad → löst med `setTraits([])`
- `ProductAttributeValue::create([])` utan bundle orsakar fel → raden togs bort

**Git commit:** `[TASK-020-01] Add led_luminaire_max_opti and led_luminaire_srow product types with attributes`

---

### TASK-020-02 ✅ Klar — 2026-03-17

**Vad gjordes:**
- `field_configurator_schema` (text_long) skapad som field storage på `commerce_product`
- Fältet kopplat till `led_luminaire_max_opti` med label "Konfigurator-schema (JSON)"
- Fältet kopplat till `led_luminaire_srow` med label "Konfigurator-schema (JSON)"

**Git commit:** ⚠️ Behövs — kör `ddev drush cex -y && git add -A && git commit -m "[TASK-020-02] Add field_configurator_schema to led_luminaire product types"`

---

### TASK-020-03: Radera befintliga felaktiga produkter

Via Drupal admin eller Drush — kontrollera att inga aktiva orders refererar till dem först.

```bash
# Kontrollera orders
ddev drush php:eval "
\$orders = \Drupal::entityTypeManager()->getStorage('commerce_order')
  ->loadByProperties(['state' => 'draft']);
echo 'Draft orders: ' . count(\$orders) . PHP_EOL;
"

# Radera alla produkter
ddev drush php:eval "
\$products = \Drupal::entityTypeManager()->getStorage('commerce_product')->loadMultiple();
foreach (\$products as \$p) { \$p->delete(); }
echo 'Deleted ' . count(\$products) . ' products' . PHP_EOL;
"
```

---

### TASK-020-04: Generera MAX BASE variations CSV

CSV baseras på `/docs/product-schemas/max-configurator-schemas.json` (slug: `max-base`).

SKU-format: `M-{length}{driver}{endcap}{cri}{kelvin}{watt}{optic}{color}`

Notera: `kelvin` har prefix `-` i SKU-koden (t.ex. `-J` = 3000K) — strippa bindestrecket i attributvärdet.

Filen sparas som: `/private/feeds/max-base-variations.csv`

---

### TASK-020-05: Feeds-instans + import MAX BASE

1. Skapa Products-feed för MAX BASE (en produkt-rad med schema-JSON)
2. Skapa Variations-feed för MAX BASE
3. Kör products-feed FÖRST
4. Kör variations-feed
5. Rensa feeds_item efter import

---

### TASK-020-06: Verifiera SKU:er och varianter

```bash
ddev drush php:eval "
\$count = \Drupal::entityTypeManager()
  ->getStorage('commerce_product_variation')
  ->getQuery()->accessCheck(FALSE)->count()->execute();
echo 'Total variations: ' . \$count . PHP_EOL;
"
```

Testa JSON:API:
`/jsonapi/commerce_product_variation/default?filter[sku][value]=M-A0C-8-J19N1`

---

## 4. VERIFY

### Acceptanskriterier att kontrollera
- [x] Två Product Types finns i admin ✅
- [x] `field_configurator_schema` finns på båda typerna ✅
- [ ] MAX BASE-produkt har schema-JSON ifyllt
- [ ] Varianter har korrekta SKU:er i M-format
- [ ] JSON:API returnerar varianter korrekt
- [ ] Inga felaktiga gamla produkter kvar

---

## 5. COMPLETION

### Status: 🔄 In Progress

### Prioritetsordning för modellimport
1. MAX BASE (proof-of-concept)
2. OPTI BASE
3. SROW BASE
4. Emergency-modeller (MAX-E, MAX-ED, OPTI-E, OPTI-ED, SROW-E, SROW-ED)
5. Sensor-modeller (MAX-S, OPTI-S)
6. MAX PRO

### Filer att referera
- `/docs/product-schemas/max-configurator-schemas.json`
- `/docs/product-schemas/opti-configurator-schemas.json`
- `/docs/product-schemas/srow-configurator-schemas.json`
- `/docs/tasks/task-015-konfigurator-arkitektur.md`
