# Task 006: Footer & Full-Width Layout

**Status:** Completed  
**Datum:** 2026-02-26

## Mål
- Mörk footer (Bootstrap secondary)
- Full-width hero (kant-till-kant)
- Grå navbar-bakgrund
- Rensa BLB-varningar

## Lösningar

### 1. Footer styling
CSS på `footer.page__footer` i `style.css`:
```css
footer.page__footer {
  background-color: var(--bs-secondary);
  color: var(--bs-white);
}
```

### 2. Full-width layout via page--front.html.twig
Radix använder `page--front.html.twig` på startsidan (INTE `page.html.twig`).
`py-5` är hårdkodat i `radix:page` - måste överskriva i custom template som kopierar `page.twig`-strukturen och skickar `page_main_utility_classes: []` direkt till `radix:page-content`.

Fil: `web/themes/custom/tritonled_radix/templates/page/page--front.html.twig`

### 3. Navbar & gap-fix
```css
body { background-color: var(--bs-gray-100); }
.page > .navbar { background-color: var(--bs-gray-100); }
.page > main { margin-top: -16px; }
.container-fluid { padding-left: 0; padding-right: 0; }
.layout-builder__layout .col-12 { padding-left: 0; padding-right: 0; }
```

Gap på 16px mellan navbar och main orsakas av subpixel-rendering - inga element äger gapet men det syns mot body-bakgrunden. Fix: `margin-top: -16px` på `main`.

### 4. Bootstrap Layout Builder NULL-attribut bug
**Problem:** `Warning: foreach() argument must be of type array|object, string given in NestedArray::mergeDeepArray()`

**Orsak:** BLB sparar `container_wrapper_attributes` och `section_attributes` som `NULL` eller `string` istället för `array` när sektioner skapas utan att fylla i dessa fält.

**Fix:** Skriv PHP-script med Filesystem:write_file, kör med `ddev drush php:script`, ta bort efteråt.

Script-innehåll:
```php
<?php
$node = \Drupal\node\Entity\Node::load(NODE_ID);
$sections = $node->get('layout_builder__layout')->getSections();
foreach ($sections as $delta => $section) {
  $config = $section->getLayoutSettings();
  if (!is_array($config['container_wrapper_attributes'])) $config['container_wrapper_attributes'] = [];
  if (!is_array($config['section_attributes'])) $config['section_attributes'] = [];
  $section->setLayoutSettings($config);
}
$node->get('layout_builder__layout')->setValue($sections);
$node->save();
echo 'Fixed!';
```

Kör på varje nod som använder Layout Builder Override om varningen uppstår.

## Lardomat

### Twig template-hierarki pa startsidan
- Drupal valjer `page--front.html.twig` fore `page.html.twig` pa startsidan
- Radix starterkit har en `page--front.html.twig` i contrib
- Custom override maste matcha exakt samma filnamn

### Radix page_main_utility_classes
- `py-5` ar hardkodat i `radix:page` komponenten
- `include` med `with` i Twig mergar INTE variabler - den skickar bara de som anges explicit
- Losning: Kopiera `page.twig`-strukturen i custom template och skicka variabler direkt till `radix:page-content`

### Bootstrap CDN och CSS-specificity
- Bootstrap laddas via CDN efter temats CSS
- Battre att ta bort klassen i Twig-template an att overskriva med CSS

### BLB NULL-attribut
- BLB sparar ibland NULL for `container_wrapper_attributes` och `section_attributes`
- Sektioner maste ha [] (tom array) inte NULL eller string

## Arbetsregel-overträdelse

Claude fortsatte att skriva temporara script-filer trots explicita instruktioner:
- Anvande bash_tool istallet for Filesystem:* verktyg
- Skapade filer pa Claudes container-dator istallet for Stefans Mac
- Dessa filer ar osynliga for Stefan och stadades inte upp

Ratt tillvagagangssatt for PHP-script:
1. Filesystem:write_file till /Users/steffes/Projekt/tritonled/filnamn.php
2. Be Stefan kora: ddev drush php:script filnamn.php
3. Be Stefan ta bort: rm filnamn.php
