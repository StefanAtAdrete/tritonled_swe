# TASK-003: Featured Products

**Created**: 2026-02-26
**Status**: ✅ KLAR
**Last Updated**: 2026-02-26

---

## Mål

Visa featured products på startsidan som ett Bootstrap Grid med kort.
- Desktop/tablet: liggande card (16:10, `card_medium` 400×300 Scale and Crop)
- Mobil: horisontell badge-layout (80×80px kvadrat + titel bredvid)

---

## Konfiguration

### Views
- **View**: `featured_products` (block)
- **Relation**: `commerce_product__variations` → variation delta 0
- **Fält**: `field_variation_media` via `entity_reference_entity_view`, `view_mode: card`
- **Style**: `views_bootstrap_grid` (col-12 / col-sm-6 / col-md-4)
- **Custom field**: Bootstrap card-markup med `card-img-wrap`-wrapper

### Image styles
- `card_medium`: 400×300, Scale and Crop (desktop/tablet)
- `card_thumbnail`: 200×200, Scale and Crop (mobil)

### Responsive image style: `card`
- `tritonled_radix.desktop` → `card_medium`
- `tritonled_radix.tablet` → `card_medium`
- `tritonled_radix.mobile` → `card_thumbnail`
- `fallback_image_style`: `card_medium` ← **viktigt, inte max_325x325**

### View modes
- `media.image.card`: formatter `responsive_image`, style `card`
- `commerce_product_variation.default.card`: formatter `responsive_image`, style `card`

---

## Felsökning och lärdomar

### Problem: Bilderna hade olika höjd
**Orsak**: `fallback_image_style` i `responsive_image.styles.card` var satt till `max_325x325` (Scale, bevarar ratio). Blazy använder fallback-stilen som `data-src` istället för `<picture>`-taggen.

**Diagnos**:
- `ddev drush php:eval` visade att `media.image.card` var korrekt i databas
- Drush-rendering av media direkt visade `card_medium` + `<picture>` ✅
- Men DOM på sidan visade `max_325x325` + `data-src` (Blazy lazy-load)
- **Slutsats**: Blazy interceptar `<picture>`-taggen och ersätter med `<img data-src>` med fallback-stilen

**Fix**: Ändra `fallback_image_style` från `max_325x325` till `card_medium` i `responsive_image.styles.card.yml`

### Problem: `card` view mode saknades för variation
**Orsak**: `commerce_product_variation.default.card` display fanns inte — Drupal föll tillbaka på `default` som kör Splide med `max_325x325`.

**Fix**: Skapa två config-filer:
- `core.entity_view_mode.commerce_product_variation.card.yml`
- `core.entity_view_display.commerce_product_variation.default.card.yml`

### Blazy + Responsive image
- Blazy ersätter `<picture>` med `<img data-src>` för lazy-loading
- `fallback_image_style` i responsive image style = den style Blazy använder
- Sätt alltid `fallback_image_style` till en **Scale and Crop**-stil, aldrig `max_*` (Scale)

---

## CSS: hero.css

```css
/* Mobil: badge-layout - fast kvadratisk bild 80x80 */
.card-img-wrap {
  flex: 0 0 80px;
  max-width: 80px;
  width: 80px;
  height: 80px;
  overflow: hidden;
  position: relative;
  flex-shrink: 0;
}

.card-img-wrap img,
.card-img-wrap video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.card-img-wrap .media,
.card-img-wrap .field,
.card-img-wrap .field__item,
.card-img-wrap picture {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

/* Desktop: liggande card med aspect-ratio */
@media (min-width: 768px) {
  .card-img-wrap {
    flex: unset;
    width: 100%;
    max-width: 100%;
    height: auto;
    aspect-ratio: 16 / 10;
  }

  .card-img-wrap .media,
  .card-img-wrap .field,
  .card-img-wrap .field__item,
  .card-img-wrap picture {
    position: static;
    inset: auto;
    width: 100%;
    height: 100%;
  }
}
```

---

## Workflow-lärdom: Config import/export

**ALLTID** i denna ordning:
```bash
ddev drush cex -y   # Exportera FÖRST
# Redigera YAML-filer
ddev drush cim -y   # Importera
ddev drush cr       # Rensa cache
```

`cim` utan föregående `cex` raderar config som finns i databasen men inte i sync.

---

**Version**: 1.0
**Författare**: Claude + Stefan
