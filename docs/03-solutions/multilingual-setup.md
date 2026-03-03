# Flerspråkighet — Drupal 11 setup

**Skapad**: 2026-03-03  
**Task**: TASK-012  

---

## Moduler som krävs

```bash
ddev drush en locale config_translation -y
ddev drush locale:update  # Hämtar alla svenska systemöversättningar
```

## Aktivera översättning per entitetstyp

```php
$manager = \Drupal::service('content_translation.manager');
$manager->setEnabled('node', 'page', TRUE);
$manager->setEnabled('commerce_product', 'default', TRUE);
$manager->setEnabled('commerce_product_variation', 'default', TRUE);
$manager->setEnabled('block_content', 'basic', TRUE);
$manager->setEnabled('menu_link_content', 'menu_link_content', TRUE);
// media: audio, document, image, remote_video, video
drupal_flush_all_caches();
```

## Skapa/uppdatera översättningar

### Noder
```php
$node = \Drupal::entityTypeManager()->getStorage('node')->load(NID);
$t = $node->addTranslation('sv', [
  'title' => 'Svenska titeln',
  'body' => ['value' => 'Brödtext...', 'format' => 'basic_html'],
]);
$t->save();
```

### Commerce-produkter
Samma metod som noder men med `commerce_product`-storage.

### Views-titlar
```php
\Drupal::languageManager()
  ->getLanguageConfigOverride('sv', 'views.view.VIEW_ID')
  ->set('display.default.display_options.title', 'Svenska titeln')
  ->save();
```

### Block-titlar (placering)
```php
\Drupal::languageManager()
  ->getLanguageConfigOverride('sv', 'block.block.BLOCK_ID')
  ->set('settings.label', 'Svenska titeln')
  ->save();
```

## Viktiga insikter

- ✅ `locale` + `config_translation` måste vara aktiverade för block/views-översättningar
- ✅ `menu_link_content` kräver content_translation aktiverat för att översättas
- ✅ `drush locale:update` importerar ~7000 svenska systemöversättningar automatiskt
- ✅ Block-innehåll (`block_content`) översätts via `addTranslation()` på entiteten
- ✅ Block-titel i placering översätts via `getLanguageConfigOverride()` på `block.block.[id]`
- ⚠️ Exportera alltid config efter `setEnabled()`: `ddev drush cex -y`
- ⚠️ Config-entiteter (taxonomier etc.) skapas via Drush/UI — aldrig manuell YAML
