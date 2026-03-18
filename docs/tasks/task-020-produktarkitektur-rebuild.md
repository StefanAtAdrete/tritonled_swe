# Task 020: Produktarkitektur Rebuild

**Created**: 2026-03-17  
**Status**: In Progress  
**Last Updated**: 2026-03-18 (arkitekturbeslut)  
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
- [x] Befintliga felaktiga produkter/varianter borttagna
- [ ] 12 Commerce-produkter skapade (en per modell)
- [ ] JSON-schema ifyllt i `field_configurator_schema` på varje produkt
- [ ] Produkterna tillgängliga via JSON:API

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
3. **Importera alla varianter som Commerce-varianter**: AVFÄRDAT 2026-03-18 — 12 modeller × ~15 000 varianter = hundratusentals rader. Onödigt, långsamt, svårt att underhålla.

### ✅ ARKITEKTURBESLUT 2026-03-18: Konfigurator-approach

**Varje modell = EN Commerce-produkt med JSON-schema i `field_configurator_schema`.**

Konfiguratorn (TASK-015) läser JSON-schemat, låter användaren välja steg för steg med dependsOn-logik, genererar SKU live och lägger direkt i cart som custom line item — **utan att Commerce-varianter behövs**.

- ✅ En produkt per modell (12 produkter totalt)
- ✅ JSON-schema i `field_configurator_schema` är produktdefinitionen
- ✅ SKU genereras dynamiskt i frontend av konfiguratorn
- ✅ Cart-integration via custom order item (inte variant-referens)
- ❌ Inga tusentals Commerce-varianter i databasen

**Godkänt av Stefan**: ✅ Godkänd

---

## 3. IMPLEMENT

### Sub-tasks

| Sub-task | Beskrivning | Status |
|----------|-------------|--------|
| TASK-020-01 | Skapa Product Types + attribut | ✅ Klar |
| TASK-020-02 | Lägg till field_configurator_schema | ✅ Klar |
| TASK-020-03 | Radera befintliga felaktiga produkter | ✅ Klar |
| TASK-020-04 | Generera MAX BASE variations CSV | ❌ Avfärdad — fel approach |
| TASK-020-05 | Feeds-instans + import MAX BASE | ❌ Avfärdad — fel approach |
| TASK-020-06 | Verifiera SKU:er och varianter | ❌ Avfärdad — fel approach |
| TASK-020-07 | Upprepa för övriga modeller | ❌ Avfärdad — fel approach |
| TASK-020-08 | Skapa en Commerce-produkt per modell (12 st) | ✅ Klar |
| TASK-020-09 | Lägg in JSON-schema i field_configurator_schema per produkt | ✅ Klar |

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

### TASK-020-03 ✅ Klar — 2026-03-18

**Vad gjordes:**
- 2 draft orders raderade (testvarukorgar)
- Alla befintliga felaktiga produkter (och deras varianter) raderade via Drush

**Git commit:** `[TASK-020-03] Delete legacy products and draft orders`

---

### TASK-020-04 ✅ Klar — 2026-03-18

**Vad gjordes:**
- `private/feeds/max-base-v2.csv` genererad med 15 840 varianter
- `private/feeds/max-base-products-v2.csv` skapad (1 produktrad)
- SKU-format: `M-{length}{driver}{endcap}{cri}-{kelvin}{watt}{optic}{color}` (t.ex. `M-A0C8-J19N1`)
- Kolumner: `sku,product_sku,status,language,attribute_length,attribute_driver,attribute_endcap,attribute_cri,attribute_kelvin,attribute_watt,attribute_optic,attribute_color`

**Git commit:** `[TASK-020-04] Generate MAX BASE products and variations CSV (15840 rows)`

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
- [x] Inga felaktiga gamla produkter kvar ✅
- [ ] 12 produkter skapade med rätt product type
- [ ] `field_configurator_schema` ifyllt med JSON per produkt
- [ ] JSON:API returnerar produkter med schema-fältet

---

## 5. COMPLETION

### Status: ✅ Completed — 2026-03-18

### 12 modeller att skapa
**MAX/OPTI (led_luminaire_max_opti):**
1. MAX BASE (slug: max-base)
2. MAX PRO (slug: max-pro)
3. MAX-S (slug: max-s)
4. MAX-E (slug: max-e)
5. MAX-ED (slug: max-ed)
6. OPTI BASE (slug: opti-base)
7. OPTI-S (slug: opti-s)
8. OPTI-E (slug: opti-e)
9. OPTI-ED (slug: opti-ed)

**SROW (led_luminaire_srow):**
10. SROW BASE (slug: srow-base)
11. SROW-E (slug: srow-e)
12. SROW-ED (slug: srow-ed)

### Filer att referera
- `/docs/product-schemas/max-configurator-schemas.json`
- `/docs/product-schemas/opti-configurator-schemas.json`
- `/docs/product-schemas/srow-configurator-schemas.json`
- `/docs/tasks/task-015-konfigurator-arkitektur.md`
