# Session: Hero Carousel & Card Layout
**Datum**: 2026-02-23

---

## Vad vi byggde

### 1. Hero Carousel - Produktfält
Lade till två fält på `commerce_product` (type: default):
- `field_show_in_hero` — Boolean, "Show in Hero carousel"
- `field_hero_media` — Media reference (image, remote_video, video), max 1

Båda fälten doldes i alla view modes (default, hero).

### 2. Feeds/fid-bugg — Produktredigering
**Symptom**: "fid not found" vid produktredigering + "modified by another user"  
**Orsak**: `feeds_item` finns på BÅDE `commerce_product` och `commerce_product_variation`  
**Fix**: Rensa feeds_item på båda efter import:
```bash
ddev drush php:eval "
\$products = \Drupal::entityTypeManager()->getStorage('commerce_product')->loadMultiple();
foreach(\$products as \$p) { \$p->set('feeds_item', []); \$p->save(); }
\$variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->loadMultiple();
foreach(\$variations as \$v) { \$v->set('feeds_item', []); \$v->save(); }
"
ddev drush cr
```
Se även: `docs/03-solutions/feeds-item-ajax-bug.md`

### 3. tritonled_compat — Layout Builder + Feeds bug
**Symptom**: `/node/*/layout` kraschade med "getType() on null in cleanIds()"  
**Orsak**: Feeds module 8.x-3.2 — `feeds_item` fälttyp anropar `generateSampleValue()` 
som försöker ladda `feeds_feed` entiteter men kraschar på `fid`-kolumnen.  
**Fix**: Custom modul `tritonled_compat` med `SafeFeedsItem`-klass som returnerar `[]` från `generateSampleValue()`.  
**Plats**: `web/modules/custom/tritonled_compat/`  
**OBS**: Modulen är **obligatorisk i produktion**.

### 4. Hero CSS — `css/hero.css`
Ny fil skapad och inlagd i `global-styling` library.

**Struktur**:
- `.view-hero .carousel-item` — aspect-ratio 1:1 mobil, 4:1 desktop
- `.carousel-caption` — `display: block !important` (Bootstrap döljer den på mobil)
- Mörk gradient-overlay via `::after`
- Video och bild fyller hela carousel-item med `object-fit: cover`

### 5. Image Styles för Cards
Skapade två nya image styles:
- `card_thumbnail` — 200x200px, scale_and_crop (mobil)
- `card_medium` — 400x300px, scale_and_crop (desktop)

Skapade responsive image style `card` med:
- 1x Mobile → `card_thumbnail`
- 1x Tablet → `card_medium`
- 1x Desktop → `card_medium`
- Fallback → `max_325x325`

Uppdaterade `media.image.card` view mode att använda `card` responsive image style.

### 6. Horizontal Card Layout (mobil)
**Views**: `featured_products` — `nothing`-fältet uppdaterat till:
```html
<div class="card h-100">
  <div class="d-flex flex-row flex-md-column">
    <div class="card-img-wrap">{{ field_variation_media }}</div>
    <div class="card-body">
      <p class="card-subtitle mb-1">{{ field_series }}</p>
      <p class="card-title mb-0">{{ title }}</p>
    </div>
  </div>
</div>
```

**CSS i `hero.css`**:
- `.card-img-wrap` — `flex: 0 0 50%`, `position: relative`
- `.card-img-wrap .media, .field, .field__item, picture` — `position: absolute; inset: 0` (mobil)
- `@media (min-width: 768px)` — återställ till `position: static` för desktop
- `.d-flex.flex-row` — `align-items: stretch` för att fylla kortets höjd

---

## Lärdomar

- `feeds_item` finns på BÅDE product och variation — rensa båda efter import
- Bootstrap döljer `.carousel-caption` på mobil — måste overridas med `!important`
- `position: absolute` på mellanliggande element (`.field__item`) krävs för att bilden ska fylla höjden på mobil, men måste återställas med `position: static` på desktop
- Views config-ändringar via fil kräver exakt timing med cex/cim — vid problem, uppdatera direkt via `Drupal::configFactory()`
- `tritonled_compat` modul är obligatorisk i produktion — dokumentera tydligt
