# Lösning: SKU-konvention & Feeds CSV-import

**Datum**: 2026-02-27  
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
- SKU måste vara **globalt unik** i hela katalogen
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

### Separator för flervärdsfält
Använd `|` för flervärdsfält, t.ex.:
- `product_features`: `Hög verkningsgrad|Lång livslängd|IP20`
- `field_certifications` (kommande): `CE|RoHS|Dimmable`

### Separata Feeds per produktserie
- Feed 1 (ID: 1) — TritonLED Products Import (produktnoder)
- Feed 2 (ID: 2) — TritonLED Variations Import (MAX-varianter)
- Planerat: separata feeds för OPTI, SROW

---

## Feeds private-mapp synk

Se `/docs/03-solutions/feeds-item-ajax-bug.md` för detaljer om private-mapp synk.
