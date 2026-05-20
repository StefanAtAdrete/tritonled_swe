# TASK-057 — Produkttyp: wall_light

**Created**: 2026-05-20
**Status**: In Progress
**Last Updated**: 2026-05-20
**Related Tasks**: TASK-056 (master), TASK-058 (bollard)

---

## 1. DEFINE

### Mål
Skapa produkttypen `wall_light` med tillhörande taxonomy term, fältstruktur, feeds och CSV-import för UpShine wall light-serier.

### Produktserier (UpShine)
| Serie | Typ | Dimning |
|---|---|---|
| WL183 | Wall Light Decorative | TRIAC / DALI |
| WL195 | Wall Light | — |
| WL199 | Wall Light | — |
| WL186A/B | Wall Light med EU-uttag | TRIAC Dim 3000K |

### Acceptanskriterier
- [x] Taxonomy term `Wall Light` (EN) / `Väggarmatur` (SV) skapad
- [x] Product type `wall_light` + variation type `wall_light_variation` skapad
- [x] Fält tilldelade (produkt + variation, fullständig uppsättning)
- [x] `field_cable` dold i adminformulär
- [x] Feeds skapade: ID 13 (products) + ID 14 (variations)
- [ ] CSV-mall skapad och dokumenterad
- [ ] Minst 1 testprodukt importerad och verifierad
- [ ] Full import av alla WL-serier
- [ ] Svensk översättning på taxonomy term ✅ (Väggarmatur)
- [ ] View-sida skapad (visar wall_light + bollard tillsammans)
- [ ] Bilder kopplade (manuellt, sista steget)

**Godkänt av Stefan**: ✅

---

## 2. PLAN

### Approach
Kopiera fältstruktur från `floodlight` + alla fält från `default`.

**Fält på variation (31 st):**
field_ambient_temp, field_beam_angle, field_case_temp, field_cct,
field_ce_marking, field_control_system, field_cri, field_current,
field_dimension_height, field_dimension_length, field_dimension_width,
field_dimmable, field_dimming_protocol, field_efficacy, field_enec,
field_energy_class, field_frequency, field_housing_color, field_ik_rating,
field_installation_notes, field_lumens, field_material, field_mounting_type,
field_operating_temp_max, field_operating_temp_min, field_power_factor,
field_rated_life, field_rohs, field_variation_media, field_warranty_years, field_weight

**Fält på produkt (19 st):**
field_brand, field_cable (dold), field_datasheet, field_dimming, field_driver,
field_features, field_hero_media, field_installation_environment, field_ip_class,
field_lumen_range, field_producers, field_product_categories, field_product_media,
field_product_sku, field_series, field_short_description, field_show_in_hero,
field_warranty, field_watt_range

**Landningssida:**
View-sida som filtrerar `product_categories` IN (`wall_light`, `bollard`).
Skapas i TASK-058 (när bollard är klar).

### Feeds
- Feed ID 13: `TritonLED Wall Light Products Import` (type: tritonled_products)
- Feed ID 14: `TritonLED Wall Light Variations Import` (type: tritonled_variations)
- CSV-fil: `private://feeds/wall-light.csv`

**Godkänt av Stefan**: ✅

---

## 3. IMPLEMENT

### ✅ Klar: Steg 1–8 (2026-05-20)
- Taxonomy term ID: 51
- Product type: `wall_light`
- Variation type: `wall_light_variation`
- Alla fält tilldelade
- field_cable dold via entity_display.repository
- Feeds skapade: ID 13 + 14
- Committed: `[TASK-057] Add wall_light product type, variation type, all fields, hide field_cable`

### Steg 9 — CSV-mall + datainmatning
Se `/docs/skills/category-c-product-import/SKILL.md`
CSV-fil: `web/sites/default/private/feeds/wall-light.csv`
Data hämtas från UpShine PDF:er (Stefan har dessa lokalt)

### Steg 10 — Testimport + rensning
1. Admin → `/admin/content/feeds` → Import feed ID 13 (products)
2. Admin → `/admin/content/feeds` → Import feed ID 14 (variations)
3. Rensa feeds_item:

```bash
ddev drush php:eval "
\$vids = \Drupal::entityQuery('commerce_product_variation')
  ->accessCheck(FALSE)->execute();
\$count = 0;
foreach (\$vids as \$vid) {
  \$v = \Drupal\commerce_product\Entity\ProductVariation::load(\$vid);
  if (\$v && \$v->hasField('feeds_item')) {
    \$v->set('feeds_item', NULL);
    \$v->save();
    \$count++;
  }
}
echo 'Cleaned: ' . \$count;
"
```

### Steg 11 — Bilder (sist, efter TASK-058)
- Samla bilder från PDF/SharePoint
- Resize: `~/Projekt/resize-images.sh`
- Upload: `media_bulk_upload` → `/admin/content/media`
- Koppla: VBO på varianter filtrade per produktserie

---

## 4. VERIFY

- [x] Produkttyp syns i `/admin/commerce/products` → "Lägg till produkt"
- [x] Variation type har rätt fält
- [ ] Testimport: produkt + varianter skapade korrekt
- [ ] feeds_item rensat — Media Library AJAX fungerar
- [ ] View-sida visar wall_light + bollard

---

## 5. COMPLETION

### Nästa steg
→ CSV-mall + datainmatning från PDF
→ TASK-058 (bollard) → gemensam View-sida
