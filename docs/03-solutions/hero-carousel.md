# Lösning: Hero Carousel med views_bootstrap + Bootstrap 5

**Skapad**: 2026-02-17
**Task**: TASK-002
**Status**: Delvis klar (E+F återstår)

---

## Problem som löstes

### 1. YAML-filer med ogiltiga UUIDs genererar inte image styles

**Problem**: Manuellt skrivna YAML-filer med egna UUID-strängar (t.ex. `hero-desktop-scale-crop`)
accepteras av Drupal men GD-toolkit kan inte processa dem — felet syns i watchdog:
```
The selected image handling toolkit 'gd' can not process operation 'image_scale_and_crop'
```

**Lösning**: Skapa alltid image styles via admin UI — Drupal genererar korrekta UUIDs automatiskt.
Exportera sedan med `ddev drush cex -y`.

**Regel**: Skriv ALDRIG image style effects manuellt i YAML. Använd admin UI.

---

### 2. views_bootstrap är inte Bootstrap 5-kompatibelt

**Problem**: `views_bootstrap` genererar Bootstrap 4-attribut:
- `data-ride="carousel"`
- `data-slide="prev/next"`
- `data-interval="5000"`

Bootstrap 5 kräver `data-bs-*` prefix — carousel fungerar inte utan detta.

**Lösning**: Bootstrap compat behavior i `global.js`:
```javascript
Drupal.behaviors.tritonledBootstrapCompat = {
  attach: function (context, settings) {
    once('bs4-to-bs5', '.carousel', context).forEach(function (el) {
      ['ride', 'interval', 'pause', 'wrap', 'keyboard'].forEach(function (attr) {
        if (el.hasAttribute('data-' + attr)) {
          el.setAttribute('data-bs-' + attr, el.getAttribute('data-' + attr));
        }
      });
      el.querySelectorAll('[data-slide]').forEach(function (btn) {
        btn.setAttribute('data-bs-slide', btn.getAttribute('data-slide'));
      });
      new bootstrap.Carousel(el);
    });
  }
};
```

**Fil**: `web/themes/custom/tritonled_radix/js/global.js`

---

### 3. Image styles genereras inte automatiskt on-demand i DDEV/nginx

**Problem**: DDEV kör nginx. Webp-filer matchas av nginx location-blocket för statiska
filer INNAN Drupal hinner generera image style-derivat. Resulterar i 404.

**Lösning**: Generera manuellt via Drush när nya bilder läggs till:
```bash
ddev drush php:eval "
\$styles = ['hero_desktop', 'hero_tablet', 'hero_mobile'];
foreach (\$styles as \$style_id) {
  \$style = \Drupal\image\Entity\ImageStyle::load(\$style_id);
  \$destination = \$style->buildUri('public://[PATH/TO/FILE]');
  \$result = \$style->createDerivative('public://[PATH/TO/FILE]', \$destination);
  echo \$style_id . ': ' . (\$result ? 'OK' : 'FAIL') . '\n';
}
"
```

**OBS**: Detta är ett DDEV-lokalt problem — produktionsmiljö hanterar detta automatiskt.

---

### 4. Config export INNAN import (kritiskt)

**Problem**: `ddev drush cim -y` raderar config som finns i databasen men INTE i config/sync.
Vi förlorade `core.entity_view_display.commerce_product_variation.default.default` på detta sätt.

**Regel**: KÖR ALLTID `ddev drush cex -y` INNAN `ddev drush cim -y`.

```bash
# RÄTT ordning:
ddev drush cex -y   # 1. Exportera befintlig config
# (lägg till/ändra YAML)
ddev drush cim -y   # 2. Importera — inget raderas
ddev drush cr       # 3. Cache clear
```

---

### 5. Media Hero view mode kräver eget view mode på Media-entiteten

**Problem**: `commerce_product` Hero view mode renderar `field_product_media` via
"Rendered entity" — men Media-entiteten har inget eget Hero view mode, vilket ger
fel bildformat.

**Lösning**: Skapa Hero view mode på BÅDE:
- `commerce_product` → Manage display → Hero
- `media` (image, video, remote_video) → Manage display → Hero

Config som skapades:
- `core.entity_view_mode.commerce_product.hero`
- `core.entity_view_mode.media.hero`
- `core.entity_view_display.media.image.hero`
- `core.entity_view_display.media.remote_video.hero`
- `core.entity_view_display.media.video.hero`
- `core.entity_view_display.commerce_product.default.hero`

---

## Konfiguration som skapades

### Image styles
- `hero_mobile` — 768×768px, focal_point_scale_and_crop (1:1)
- `hero_tablet` — 1200×300px, focal_point_scale_and_crop (4:1)
- `hero_desktop` — 1920×480px, focal_point_scale_and_crop (4:1)

### Responsive image style
- `hero_responsive` — mappar breakpoints från `tritonled_radix.breakpoints.yml`

### Views
- `views.view.hero` — Bootstrap carousel, Commerce produkter, Hero view mode

---

## Återstår (SUB-TASK E + F)

- Fullbredd sektion i Layout Builder (ingen container)
- Styling: overlay, text-position, aspect-ratio CSS

---

**Version**: 1.0
**Skapad**: 2026-02-17
**Författare**: Claude + Stefan
