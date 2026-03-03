# Lösning: SKU-konvention & Feeds CSV-import

**Datum**: 2026-02-27  
**Uppdaterad**: 2026-03-03  
**Gäller**: Commerce produktvarianter, Feeds CSV-import

---

## SKU-konvention

### Triton MAX
Format: `TM-{längd}-{watt}-{CCT}-{accessory}-{driver}[-{suffix}]`

| Del | Värden | Exempel |
|-----|--------|---------|
| Produktkod | TM = Triton MAX | TM |
| Längd | 05, 10, 15, 20 (×100mm) | 05 = 500mm |
| Watt | Numeriskt | 29 |
| CCT | 3K, 4K, 5K, 6K | 4K = 4000K |
| Accessory | W1, W2, W3, CG, EN | CG |
| Driver | AP (OnOff), D2 (DALI2) | AP |
| Suffix | IP-klass om ej IP20 | IP43 |

### Triton OPTI
Format: `TO-{längdkod}{watt}-{CCT}-{driver}-{connection}-{beam}-IP{ip}`

| Del | Värden | Exempel |
|-----|--------|---------|
| Produktkod | TO = Triton OPTI | TO |
| Längdkod | A=600mm, B=1150mm, C=1725mm, D=2005mm | A |
| Watt | Numeriskt | 22 |
| CCT | 4000 | 4000 |
| Driver | K0=OnOff, K2=DALI2, K3=B2L | K2 |
| Connection | W1=Wago, EN=EnstoNet | W1 |
| Beam | 30, 60, 80, 105, 110 | 30 |
| IP | 20, 40 | IP20 |

Exempel: `TO-A22-4000-K0-W1-30-IP20`

### Viktigt
- SKU måste vara globalt unik i hela katalogen
- IP-klass ingår alltid i OPTI-SKU (IP20/IP40 är separata varianter)
- B2L-driver finns bara på D/2005mm 162W-varianten

---

## Feed-struktur (separata feeds per produktserie)

### Befintliga feeds
| Feed ID | Label | Typ | CSV-fil |
|---------|-------|-----|---------|
| 1 | TritonLED Products Import | tritonled_products | master_3.csv |
| 2 | TritonLED Variations Import | tritonled_variations | master_4.csv |
| 3 | TritonLED OPTI Products Import | tritonled_products | triton-opti.csv |
| 4 | TritonLED OPTI Variations Import | tritonled_variations | triton-opti.csv |

### CSV-filer
```
private/feeds/
├── master_3.csv        ← MAX + SROW produkter
├── master_4.csv        ← MAX + SROW varianter
├── triton-opti.csv     ← OPTI produkter + varianter (500 varianter)
└── ...
```

### Filosofi
- En CSV per produktserie — enklare att underhålla
- Samma feed-typ (template) — olika instanser med olika källfil
- Cron kör alla feeds automatiskt på prod
- Views CSV-export för att exportera hela katalogen

---

## Feeds CSV-struktur

### Kolumner (master)
```
product_sku, product_title, product_brand, product_series,
product_short_description, product_body, product_features,
product_status, store_id, sku, price, currency, variation_status,
attribute_watt, attribute_cct, attribute_ip_rating, attribute_length,
attribute_voltage, attribute_connection, attribute_bracket,
attribute_beam_angle, attribute_color, attribute_cri,
field_lumens, field_efficacy, field_power_factor, field_current,
field_frequency, field_dimmable, field_dimming_protocol,
field_control_system, field_rated_life, field_warranty_years,
field_energy_class, field_material, field_housing_color,
field_mounting_type, field_weight, field_dimension_length,
field_dimension_width, field_dimension_height,
field_operating_temp_min, field_operating_temp_max,
field_ambient_temp, field_case_temp, field_ik_rating,
field_rohs, field_ce_marking, field_enec, field_installation_notes,
field_variation_media
```

### Attribute_connection värden
- `Wago (W1)` — standard Wago
- `Wago D2 (W2)` — Wago D2
- `Wago Black Infinity (W3)` — Wago Black Infinity
- `EnstoNet (EN)` — Ensto
- `Cable Gland (CG)` — kabelgenomföring

### Field_dimming_protocol värden (driver)
- `OnOff` — ingen dimning
- `DALI2` — DALI 2
- `B2L` — Bluetooth to Light

---

## Viktiga lärdomar

### 1. Feed label måste sättas vid skapande
Feeds skapade programmatiskt via `entityTypeManager()->create()` kan få tom label
vilket kraschar hela sajten och Drush-kommandon.

**Lösning:** Sätt label via `title`-fältet:
```php
$feed->set('title', 'TritonLED OPTI Products Import');
$feed->save();
```

**Verifiera:**
```bash
ddev drush php:eval "
\$feed = \Drupal::entityTypeManager()->getStorage('feeds_feed')->load(3);
echo \$feed->label() . PHP_EOL;
"
```

### 2. Media-referens i Feeds — KRITISKT
Feeds media-mappning har tre inställningar som ALLA måste vara korrekta:

| Inställning | Fel värde | Rätt värde |
|-------------|-----------|------------|
| reference_by | name | mid (Media ID) |
| autocreate | 1 | 0 |
| CSV-kolumn | bildnamn | numeriskt media ID |

**Symptom på fel:** Feeds skapar ny media-entitet med label = siffran från CSV
istället för att referera befintlig.

**Fix via Drush:**
```bash
ddev drush php:eval "
\$type = \Drupal::entityTypeManager()->getStorage('feeds_feed_type')->load('tritonled_variations');
\$mappings = \$type->getMappings();
foreach(\$mappings as \$key => \$m) {
  if(isset(\$m['target']) && \$m['target'] === 'field_variation_media') {
    \$mappings[\$key]['settings']['reference_by'] = 'mid';
    \$mappings[\$key]['settings']['autocreate'] = 0;
  }
}
\$type->setMappings(\$mappings);
\$type->save();
echo 'Klart' . PHP_EOL;
"
```

### 3. Bulk upload och alt-text
Bilder uppladdade via `media_bulk_upload` saknar alt-text och har filnamnet
som title. Detta kan orsaka problem med rendering.

**Fix:**
```bash
ddev drush php:eval "
\$m = \Drupal::entityTypeManager()->getStorage('media')->load(27);
\$m->get('field_media_image')->alt = 'Beskrivande alt-text';
\$m->get('field_media_image')->title = '';
\$m->save();
"
```

### 4. Media ID per connection (OPTI)
| Connection | Media ID | Filnamn |
|------------|----------|---------|
| Wago (W1) | 28 | TO_WagoW1(s).png |
| EnstoNet (EN) | 27 | TO_Ensto(s).png |

### 5. Feeds import via Drush kraschar med batch
`ddev drush feeds:import {id}` kraschar med TypeError om feed label är null.
Använd alltid UI för import, eller säkerställ att label är satt.

### 6. Namnkollision med Drupal-fält
Feeds CSV-kolumnnamn får INTE ha samma namn som Drupal-fält om de ska fungera
som custom sources.

Fel: `field_certifications` → Feeds source: `field_certifications_new` (BRUTEN)
Rätt: `certifications` → Feeds source: `certifications` (FUNGERAR)

---

## Certifieringar - separata boolean-fält

Certifieringar hanteras som separata boolean-fält på varianten, INTE som taxonomy terms:

| CSV-kolumn | Fält | Beskrivning |
|------------|------|-------------|
| field_rohs | Boolean | RoHS Compliant |
| field_ce_marking | Boolean | CE Marking |
| field_enec | Boolean | ENEC Certified |
| field_dimmable | Boolean | Dimmable |
| field_ik_rating | Text | IK-klass (t.ex. IK06) |

---

## Skapa ny feed programmatiskt (korrekt)

```bash
ddev drush php:eval "
\$feed = \Drupal::entityTypeManager()->getStorage('feeds_feed')->create([
  'type' => 'tritonled_variations',
  'title' => 'TritonLED OPTI Variations Import',
  'source' => 'private://feeds/triton-opti.csv',
  'status' => 1,
]);
\$feed->save();
echo 'Feed ID: ' . \$feed->id() . PHP_EOL;
"
```

---

## Research-checklista innan nya fält skapas

ALLTID gör detta innan du lägger till ett nytt fält:

1. Lista befintliga fält på entiteten
2. Finns liknande fält redan? Använd dem
3. Är ett boolean-fält tillräckligt, eller behövs taxonomy?
4. Taxonomy motiveras endast om: logotyper, hierarki, eller filtrering i Views behövs

```bash
ddev drush php:eval "
\$fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('commerce_product_variation', 'default');
foreach(\$fields as \$name => \$def) {
  if (str_starts_with(\$name, 'field_')) echo \$name . ' (' . \$def->getType() . ')' . PHP_EOL;
}
"
```
