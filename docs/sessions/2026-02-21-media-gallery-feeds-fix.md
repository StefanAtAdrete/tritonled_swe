# Session 2026-02-21 - Media Gallery & Feeds Bug Fix

## Vad vi byggde

### Splide Gallery på Variation Display
- Konfigurerade `field_variation_media` på variation display med formatter **Splide Media**
- Label: Visually Hidden
- Optionsets: `product_main` (huvud) och `product_nav` (thumbnails)
- Gjordes via direkt YAML-redigering i `config/sync/core.entity_view_display.commerce_product_variation.default.default.yml`

**Varför variation och inte produkt:**
Commerce AJAX ersätter variation-markup när attribut ändras. Splide på variation = automatiska gallery-uppdateringar. Splide på produkt = statisk, följer inte med.

### Datasheet PDF-fält (omgjort)
- **Problem**: Skapades ursprungligen som File-fält (`field_field_datasheet`, dubbelprefix)
- **Lösning**: Borttaget och ersatt med Media-fält (`field_datasheet`, target: Document media type)
- **Varför**: Enhetlighet – allt media går via Media-entiteter

---

## Bugg: "Add media" gav 500-fel

### Symptom
Klick på "Add media" på variation-editorn → 500 Internal Server Error, ingen dialog öppnades.

### Felsökningsprocess
1. Watchdog visade: `'fid' not found in Tables->ensureEntityTable()`
2. Stack trace pekade på `ValidReferenceConstraintValidator`
3. Identifierade att felet kom från `feeds_item`-fältet (inte från media-fältet)
4. `feeds_item` hade `target_id: 3` (referens till feeds_feed entity)
5. `feeds_feed` entity var trasig – gick inte att ladda

### Rotorsak
`feeds_item`-fältet på variationerna hade references till `feeds_feed` entities. Vid formulärvalidering (som körs vid AJAX) validerades alla entity reference-fält inklusive `feeds_item`. `ValidReferenceConstraintValidator` körde en entity query på `feeds_feed` som sökte `fid` men misslyckades pga inkorrekt entity definition i cache.

### Lösning
Rensade `feeds_item`-värdet på alla varianter:
```php
$variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->loadMultiple();
foreach($variations as $v) {
  $v->set('feeds_item', []);
  $v->save();
}
```

**OBS**: `feeds_item` sätts om automatiskt vid nästa CSV-import. Detta är en temporär fix som måste göras om efter varje import tills Feeds-buggen är fixad i modulen.

---

## Lärdomar

### 1. Feeds-modul har en bugg med entity validation
`feeds_item` fältet på importerade entiteter kan orsaka 500-fel på AJAX-formulär. Symptom: `'fid' not found`. Lösning: rensa `feeds_item` på berörda entiteter.

**Att bevaka**: Håll koll på Feeds module updates (nuvarande: 8.x-3.2). Buggen kan vara fixad i senare versioner.

### 2. File-fält vs Media-fält
Skapa ALDRIG File-fält för mediafiler på entiteter som också har Media Library-widgets. File-fält och Media Library-widgets kolliderar vid AJAX-validering. Använd alltid Media-entiteter (image, document, video) för allt medieinnehåll.

### 3. Config YAML-redigering för display
Enklare att redigera display-config direkt i YAML än via Drupal UI när:
- UI har buggar (t.ex. fel knapp öppnas)
- Flera inställningar ska sättas samtidigt
- Splide-formatter har specifika settings som inte sparas korrekt via UI

Mall för Splide Media formatter i entity_view_display YAML:
```yaml
field_variation_media:
  type: splide_media
  label: visually_hidden
  settings:
    optionset: product_main
    optionset_nav: product_nav
    media_switch: ''
    image: ''
    image_style: ''
    thumbnail_style: ''
    thumbnail_effect: ''
    view_mode: default
    loading: lazy
    vanilla: false
    preload: false
    svg_sanitize: true
  third_party_settings: {  }
  weight: -1
  region: content
```

### 4. Felsökning av 500-fel på AJAX
Rätt ordning för felsökning:
```bash
# 1. Kolla watchdog
ddev drush watchdog:show --count=5 --severity=Error

# 2. Hämta full stack trace
ddev drush php-eval "
\$row = \Drupal::database()->query('SELECT message, variables FROM watchdog ORDER BY wid DESC LIMIT 1')->fetchObject();
\$vars = unserialize(\$row->variables);
echo \$vars['@backtrace_string'];
"

# 3. Testa validate() direkt på entiteten
ddev drush php-eval "
\$entity = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load(ID);
foreach(\$entity->getFields() as \$name => \$field) {
  try { \$field->validate(); } catch(\Exception \$e) {
    echo 'FAILED: ' . \$name . ' - ' . \$e->getMessage() . PHP_EOL;
  }
}
"
```

---

## Pending

- [ ] Styla Splide thumbnail-navigeringen (utseende)
- [ ] Testa AJAX gallery-switch när variant byts
- [ ] `ddev drush cex -y` för att exportera alla config-ändringar
- [ ] Kom ihåg: rensa `feeds_item` igen efter nästa CSV-import
