# Problem: Media-bilder renderas inte i Hero-carousel

## Symptom
- Bild visas som liten thumbnail eller bruten i hero-carousel
- Bild visas korrekt på produktsidan
- `ddev drush image:flush --all` hjälper inte

## Rotsak
Drupal sparar ibland uppladdade filer som **temporära** (`status: 0`) även när de är kopplade till ett media-objekt. Drupal genererar inte image styles för temporära filer, vilket gör att responsive images inte fungerar.

## Diagnostik

### 1. Hitta vilket media-objekt produkten använder
```bash
ddev drush php-eval "
\$product = \Drupal\commerce_product\Entity\Product::load(PRODUCT_ID);
\$media = \$product->get('field_product_media')->referencedEntities();
foreach(\$media as \$m) {
  echo \$m->id() . ' - ' . \$m->bundle() . ' - ' . \$m->label() . PHP_EOL;
}
"
```

### 2. Kolla filens status
```bash
ddev drush php-eval "
\$m = \Drupal\media\Entity\Media::load(MEDIA_ID);
\$fid = \$m->get('field_media_image')->target_id;
\$f = \Drupal\file\Entity\File::load(\$fid);
echo 'URI: ' . \$f->getFileUri() . PHP_EOL;
echo 'Status: ' . \$f->get('status')->value . PHP_EOL;
echo 'Exists: ' . (file_exists(\Drupal::service('file_system')->realpath(\$f->getFileUri())) ? 'JA' : 'NEJ') . PHP_EOL;
"
```

## Lösning (manuell workaround)

### Steg 1: Sätt filen som permanent
```bash
ddev drush php-eval "
\$f = \Drupal\file\Entity\File::load(FILE_ID);
\$f->setPermanent();
\$f->save();
echo 'Fixed: ' . \$f->getFileUri() . PHP_EOL;
"
```

### Steg 2: Generera image styles
```bash
ddev drush php-eval "
\$styles = ['hero_desktop', 'hero_mobile', 'hero_tablet'];
foreach(\$styles as \$style_name) {
  \$style = \Drupal\image\Entity\ImageStyle::load(\$style_name);
  \$uri = 'public://2026-02/FILNAMN.webp';
  \$dest = \$style->buildUri(\$uri);
  \$style->createDerivative(\$uri, \$dest);
  echo \$style_name . ': OK' . PHP_EOL;
}
"
```

### Steg 3: Rensa cache
```bash
ddev drush cr
```

## Kolla alla media-filers status
```bash
ddev drush php-eval "
\$medias = \Drupal\media\Entity\Media::loadMultiple();
foreach(\$medias as \$m) {
  if (\$m->bundle() == 'image') {
    \$fid = \$m->get('field_media_image')->target_id;
    \$f = \Drupal\file\Entity\File::load(\$fid);
    if (\$f) echo \$m->id() . ' - ' . \$m->label() . ' - status:' . \$f->get('status')->value . PHP_EOL;
  }
}
"
```

## TODO: Permanent lösning
Drupal har ett känt problem med temporära filer i Media Library. Möjliga lösningar att undersöka:

- **hook_file_validate** eller **hook_media_presave** – sätt status permanent vid sparande
- **Modul**: `file_entity` eller `media_entity` har ibland inbyggd hantering
- **Cron**: Drupals filrensning (`system.file` → `temporary_maximum_age`) rensar temporära filer efter X timmar – detta kan vara rotsaken om bilder fungerar direkt men försvinner senare
- **Config**: `system.file.yml` → `temporary_maximum_age: 0` inaktiverar rensning (ej rekommenderat i produktion)

## Datum
Dokumenterat: 2026-02-20
