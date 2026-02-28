# Lösning: SKU-konvention & Feeds CSV-import

**Datum**: 2026-02-27  
**Uppdaterad**: 2026-02-28  
**Gäller**: Commerce produktvarianter, Feeds CSV-import

---

## SKU-konvention

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

### Exempel
- `TM-05-29-4K-CG-AP` — 500mm, 29W, 4000K, Cable Gland, OnOff, IP20
- `TM-05-29-4K-CG-AP-IP43` — samma men IP43

### Viktigt
- SKU måste vara globalt unik i hela katalogen
- IP-klass läggs till som suffix när den avviker från standard (IP20)
- Separata Feeds-importers per produktserie (MAX, OPTI, SROW)

---

## Feeds CSV-struktur

### Kolumner (aktuell)
```
product_sku, product_title, product_brand, product_series,
product_short_description, product_body, product_features,
product_status, store_id, sku, price, currency,
attribute_watt, attribute_cct, attribute_ip_rating,
attribute_length, attribute_voltage, attribute_accessories,
field_lumens, field_efficacy, field_cri, field_power_factor,
field_current, field_dimmable, field_dimming_protocol,
field_rated_life, field_warranty_years, field_material,
field_housing_color, field_mounting_type, field_weight,
field_dimension_length, field_dimension_width, field_dimension_height,
field_operating_temp_min, field_operating_temp_max,
field_rohs, field_ce_marking, variation_status
```

### Certifieringar - separata boolean-falt (INTE taxonomy)

Certifieringar hanteras som separata boolean-falt pa varianten, INTE som taxonomy terms:

| CSV-kolumn | Falt | Beskrivning |
|------------|------|-------------|
| field_rohs | Boolean | RoHS Compliant |
| field_ce_marking | Boolean | CE Marking |
| field_enec | Boolean | ENEC Certified |
| field_dimmable | Boolean | Dimmable |
| field_ik_rating | Text | IK-klass (t.ex. IK06) |

Varfor inte taxonomy? Befintliga boolean-falt tacker behovet. Taxonomy ar onodigt
komplext och kraver Feeds Tamper for multi-value import.

### Separator for flervardesfalt
Anvand | for flervardesfalt, t.ex.:
- product_features: Hog verkningsgrad|Lang livslangd|IP20

### Separata Feeds per produktserie
- Feed 1 (ID: 1) - TritonLED Products Import (produktnoder)
- Feed 2 (ID: 2) - TritonLED Variations Import (MAX-varianter)
- Planerat: separata feeds for OPTI, SROW

---

## Viktiga regler for CSV-kolumnnamn i Feeds

### Namnkollision med Drupal-falt
Feeds CSV-kolumnnamn far INTE ha samma namn som Drupal-falt om de ska fungera
som custom sources. Feeds lagger till suffixet _new automatiskt vilket bryter mappningen.

Fel:
CSV-kolumn: field_certifications  =>  Feeds source: field_certifications_new (BRUTEN)

Ratt:
CSV-kolumn: certifications  =>  Feeds source: certifications (FUNGERAR)

Regel: Anvand kolumnnamn utan field_-prefix for custom sources som inte
mappar direkt till ett falt med samma namn.

### Feeds Tamper - Explode for multi-value
Om ett CSV-falt innehaller flera varden separerade med |:
1. Lagg till mappning i Feeds
2. Ga till Tamper-fliken for feed-typen
3. Lagg till Explode-plugin pa kallan med separator |
4. Feeds Tamper sparar config i feeds.feed_type.[id] - exporteras via cex

---

## Feeds private-mapp synk

Se /docs/03-solutions/feeds-item-ajax-bug.md for detaljer om private-mapp synk.

---

## Research-checklista innan nya falt skapas

ALLTID gor detta innan du lagger till ett nytt falt:

1. Lista befintliga falt pa entiteten
2. Finns liknande falt redan? Anvand dem
3. Ar ett boolean-falt tillrackligt, eller behovs taxonomy?
4. Taxonomy motiveras endast om: logotyper, hierarki, eller filtrering i Views behovs

Kommando for att lista alla falt pa en variation:
ddev drush php:eval "
\$fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('commerce_product_variation', 'default');
foreach(\$fields as \$name => \$def) {
  if (str_starts_with(\$name, 'field_')) echo \$name . ' (' . \$def->getType() . ')' . PHP_EOL;
}
"
