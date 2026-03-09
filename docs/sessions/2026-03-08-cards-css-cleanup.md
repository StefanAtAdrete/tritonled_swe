/**
 * Session 2026-03-08 — Cards CSS-städning och produktkort-styling

## Problem
Produktkortens bilder (Featured Products) visades med olika beskärning — armaturerna klipptes.

## Rotorsak
1. `object-fit: cover` + fel `aspect-ratio: 16/10` i CSS styrde bildhöjden, inte image style.
2. CSS för produktkort låg felaktigt i `hero.css` (blandat med hero-karusell-CSS).

## Vad vi försökte (onödigt)
- Installerade `drupal/image_effects` och lade till Canvas-effect på image styles
- Ändrade `card_medium` image style till Scale + Canvas 400×225 transparent
- Detta löste ingenting — CSS överstyrde ändå bildstorlek

## Verklig lösning
Problemet var CSS, inte image styles. Ändrade `aspect-ratio: 16/10` → `16/9` och
flyttade `.card-img-wrap`-reglerna till rätt fil.

## Ändringar

### Ny fil: `css/components/cards.css`
- Skapad för all CSS relaterad till produktkort och kundcase-kort
- Innehåller `.card-img-wrap` med korrekt `aspect-ratio: 16/9` på desktop
- Registrerad i `tritonled_radix.libraries.yml` under `global-styling`

### `css/hero.css`
- Borttaget: hela `.card-img-wrap`-sektionen (felplacerad)
- Kvar: enbart hero-karusell-CSS

### `tritonled_radix.libraries.yml`
- Lagt till: `css/components/cards.css: {}` under `global-styling`

## Kortdesign via Views Custom text
Bootstrap-klasser läggs direkt i Views Global: Custom text — ingen CSS behövs:

### Featured Products (Commerce Product)
```html
<div class="card h-100 border-0 shadow-sm">
  <div class="d-flex flex-row flex-md-column">
    <div class="card-img-wrap">{{ field_variation_media }}</div>
    <div class="card-body bg-light">
      <p class="card-title mb-0"><a href="{{ view_commerce_product }}" class="h4 text-dark text-decoration-none fw-semibold">{{ title__value }}</a></p>
    </div>
  </div>
</div>
```

### Kundcase (Content/Node)
```html
<div class="card h-100 border-0 shadow-sm">
  <div class="d-flex flex-row flex-md-column">
    <div class="card-img-wrap">{{ field_media }}</div>
    <div class="card-body bg-light">
      <p class="card-title mb-0"><a href="{{ path }}" class="h4 text-dark text-decoration-none fw-semibold">{{ title__value }}</a></p>
    </div>
  </div>
</div>
```

Bootstrap-klasser använda:
- `border-0` — tar bort Bootstrap-border
- `shadow-sm` — subtil skugga
- `bg-light` — ljusgrå bakgrund på text-sektionen
- `text-dark text-decoration-none fw-semibold` — länkstil på titeln

## Lärdomar: Views tokens för länk-styling

### Tokens för URL
- `{{ view_commerce_product }}` = ren URL för Commerce-produkter ✅
- `{{ path }}` = ren URL för noder/content ✅
- `{{ view_node }}` = komplett `<a href="...">titel</a>`-tagg — INTE bara URL! ✗

### HTML i Views Override-fält
- HTML-override fungerar i **Global: Custom text**-fältet ✅
- HTML-override i enskilda fält (t.ex. Title) escaper HTML → renderas som text ✗
- Rätt ställe: alltid Global: Custom text för HTML-struktur

### title__value vs title
- `{{ title }}` = komplett `<a>`-tagg med länk
- `{{ title__value }}` = bara texten utan tagg — använd när du bygger egen länk

## Öppet
- `ddev drush cex -y` bör köras för att exportera image style-ändringar

## Notering
`drupal/image_effects` är installerad men Canvas-effecten är inte längre nödvändig
för produktkorten. Modulen kan behållas för framtida bruk eller avinstalleras.
