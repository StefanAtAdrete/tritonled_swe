# Drupal Coding Standards - TritonLED

## 🎯 OOP Principer

**Drupal 11 använder:**
- PSR-4 autoloading
- Dependency Injection
- Services
- Event Subscribers
- Plugins

**ALDRIG använd** `\Drupal::` statiska calls (förutom i `.module` hooks)

---

## 📁 Filstruktur

### Custom Module

```
/web/modules/custom/tritonled_custom/
├── tritonled_custom.info.yml
├── tritonled_custom.module
├── tritonled_custom.services.yml
├── composer.json (om dependencies)
├── README.md
├── config/
│   ├── install/
│   │   └── tritonled_custom.settings.yml
│   └── schema/
│       └── tritonled_custom.schema.yml
├── src/
│   ├── Controller/
│   │   └── ExampleController.php
│   ├── EventSubscriber/
│   │   └── CommerceEventSubscriber.php
│   ├── Form/
│   │   └── SettingsForm.php
│   ├── Plugin/
│   │   └── Block/
│   │       └── ExampleBlock.php
│   └── Service/
│       └── ProductHelper.php
└── templates/
    └── example-template.html.twig
```

### Custom Theme

```
/web/themes/custom/tritonled/
├── tritonled.info.yml
├── tritonled.libraries.yml
├── tritonled.theme
├── composer.json
├── README.md
├── css/
│   ├── global.css
│   └── components/
│       ├── hero-section.css
│       └── product-card.css
├── js/
│   └── tritonled.js
├── templates/
│   ├── content/
│   │   └── node--article.html.twig
│   ├── commerce/
│   │   └── commerce-product.html.twig
│   └── layout/
│       └── page.html.twig
└── images/
    └── logo.svg
```

---

## 💉 Dependency Injection

### ❌ GÖR INTE:

```php
<?php
// Använd ALDRIG statiska calls
$node = \Drupal::entityTypeManager()->getStorage('node')->load(1);
$config = \Drupal::config('system.site');
```

### ✅ GÖR:

**Service:**
```php
<?php

namespace Drupal\tritonled_custom\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class ProductHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ProductHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Get featured products.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface[]
   *   Array of featured products.
   */
  public function getFeaturedProducts() {
    $storage = $this->entityTypeManager->getStorage('commerce_product');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('field_featured', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 10);
    
    $ids = $query->execute();
    return $storage->loadMultiple($ids);
  }

}
```

**tritonled_custom.services.yml:**
```yaml
services:
  tritonled_custom.product_helper:
    class: Drupal\tritonled_custom\Service\ProductHelper
    arguments:
      - '@entity_type.manager'
      - '@config.factory'
```

**Använd service:**
```php
<?php
// I controller/form/plugin
$product_helper = \Drupal::service('tritonled_custom.product_helper');
$products = $product_helper->getFeaturedProducts();
```

---

## 🎯 Event Subscribers (istället för hooks)

### Commerce AJAX Example

**src/EventSubscriber/CommerceEventSubscriber.php:**
```php
<?php

namespace Drupal\tritonled_custom\EventSubscriber;

use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for Commerce product variations.
 */
class CommerceEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE => ['onVariationChange', -100],
    ];
  }

  /**
   * Responds to product variation AJAX change event.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent $event
   *   The event.
   */
  public function onVariationChange(ProductVariationAjaxChangeEvent $event) {
    $response = $event->getResponse();
    $variation = $event->getProductVariation();
    
    // Add custom data to AJAX response
    $response->addCommand(new InvokeCommand('.product-sku', 'text', [
      $variation->getSku()
    ]));
    
    // Log for debugging
    \Drupal::logger('tritonled_custom')->info(
      'Variation changed to @sku',
      ['@sku' => $variation->getSku()]
    );
  }

}
```

**tritonled_custom.services.yml:**
```yaml
services:
  tritonled_custom.commerce_event_subscriber:
    class: Drupal\tritonled_custom\EventSubscriber\CommerceEventSubscriber
    tags:
      - { name: event_subscriber }
```

---

## 📝 Kommentarer & Dokumentation

### Funktioner

**GÖR:**
```php
<?php

/**
 * Gets the technical specifications for a product variation.
 *
 * Fetches and formats technical specs like watt, CCT, CRI, IP rating
 * for display on product pages.
 *
 * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
 *   The product variation.
 *
 * @return array
 *   Render array of specifications.
 *   Keys: 'watt', 'cct', 'cri', 'ip_rating'
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 */
public function getSpecifications(ProductVariationInterface $variation) {
  // Implementation
}
```

### Inline-kommentarer

**Förklara VARFÖR, inte VAD:**

**❌ DÅLIGT:**
```php
// Set status to 1
$node->setPublished(TRUE);

// Loop through variations
foreach ($variations as $variation) {
```

**✅ BRA:**
```php
// Ensure product is visible to end users.
$node->setPublished(TRUE);

// Check each variation for stock availability before displaying.
foreach ($variations as $variation) {
```

---

## 🏗️ Extend, inte Override

### Extend klasser

**✅ GÖR:**
```php
<?php

namespace Drupal\tritonled_custom\Service;

use Drupal\commerce_product\ProductVariationStorage as BaseStorage;

/**
 * Extended product variation storage.
 */
class ProductVariationStorage extends BaseStorage {

  /**
   * {@inheritdoc}
   */
  public function loadEnabled(ProductInterface $product) {
    $variations = parent::loadEnabled($product);
    
    // Add custom filtering: only in-stock variations
    return array_filter($variations, function($variation) {
      return $variation->get('field_in_stock')->value;
    });
  }

}
```

**Registrera i services.yml:**
```yaml
services:
  commerce_product.variation_storage:
    class: Drupal\tritonled_custom\Service\ProductVariationStorage
    decorates: commerce_product.variation_storage
```

---

## 🔧 Preprocess Hooks (tema)

**tritonled.theme:**
```php
<?php

/**
 * @file
 * Theme functions for TritonLED theme.
 */

use Drupal\Core\Template\Attribute;
use Drupal\media\MediaInterface;

/**
 * Implements hook_preprocess_HOOK() for responsive_image.
 *
 * Removes width/height attributes to allow CSS aspect-ratio to work.
 */
function tritonled_preprocess_responsive_image(&$variables) {
  // Remove hardcoded dimensions for responsive images.
  unset($variables['img_element']['#attributes']['width']);
  unset($variables['img_element']['#attributes']['height']);
}

/**
 * Implements hook_preprocess_HOOK() for node.
 *
 * Adds CSS classes based on node properties.
 */
function tritonled_preprocess_node(&$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];
  
  // Add content type class.
  $variables['attributes']['class'][] = 'node--type-' . $node->bundle();
  
  // Add promoted class for featured content.
  if ($node->isPromoted()) {
    $variables['attributes']['class'][] = 'node--promoted';
  }
}

/**
 * Implements hook_preprocess_HOOK() for commerce_product.
 *
 * Attach product-specific JS library.
 */
function tritonled_preprocess_commerce_product(&$variables) {
  /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
  $product = $variables['product_entity'];
  
  // Attach library for product gallery functionality.
  if ($product->hasField('field_images') && !$product->get('field_images')->isEmpty()) {
    $variables['#attached']['library'][] = 'tritonled/product-gallery';
  }
}
```

---

## 🎨 Template Best Practices

### Minimal Override

**❌ DÅLIGT** (hårdkodat innehåll):
```twig
<article class="product">
  <h1>Product Title</h1>
  <div class="price">SEK 2,995</div>
</article>
```

**✅ BRA** (använd Drupal rendering):
```twig
{#
/**
 * @file
 * Template for product display.
 *
 * Available variables:
 * - product_entity: Product entity.
 * - content: Render array of product fields.
 */
#}
<article{{ attributes.addClass('product') }}>
  {{ content.title }}
  {{ content.field_images }}
  {{ content.variations }}
</article>
```

### Bootstrap Integration

**✅ BRA** (Bootstrap structure):
```twig
<article{{ attributes.addClass('product', 'card', 'shadow-sm') }}>
  <div class="row g-0">
    <div class="col-md-6">
      {{ content.field_images }}
    </div>
    <div class="col-md-6">
      <div class="card-body">
        {{ content.title }}
        {{ content.body }}
        {{ content.variations }}
      </div>
    </div>
  </div>
</article>
```

---

## 🧹 Code Quality

### PSR-12 Standards

```php
<?php

namespace Drupal\tritonled_custom\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Product helper service.
 */
class ProductHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ProductHelper object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Example method.
   */
  public function exampleMethod($param1, $param2) {
    if ($param1 === 'value') {
      return $param2;
    }
    
    return NULL;
  }

}
```

**Viktigt:**
- Indentation: 2 spaces
- Brace placement: Same line för klasser/funktioner
- Max line length: 80 characters (soft limit)

---

## 🧪 Debugging

### Kint (Development)

```php
<?php
// I kod (med devel module)
kint($variable);

// I templates
{{ kint(content) }}
```

### Watchdog Logging

```php
<?php
\Drupal::logger('tritonled_custom')->notice(
  'Product @title updated',
  ['@title' => $product->label()]
);

\Drupal::logger('tritonled_custom')->error(
  'Failed to process variation @id: @error',
  [
    '@id' => $variation->id(),
    '@error' => $exception->getMessage(),
  ]
);
```

**Visa logs:**
```bash
ddev drush watchdog:show
ddev drush watchdog:show --severity=Error
```

---

## 🚫 Säkerhet

### Sanitize Output

**I templates:**
```twig
{# Auto-escaped #}
{{ content.title }}

{# Manual escape #}
{{ user_input|escape }}

{# Render array (safe) #}
{{ content.field_name }}

{# ALDRIG raw utan sanitizing #}
{# {{ user_input|raw }} #}
```

**I PHP:**
```php
<?php
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

// Escape HTML
$safe = Html::escape($user_input);

// Filter XSS
$filtered = Xss::filter($user_input);

// Render array (auto-escaped)
$build = ['#markup' => $user_input];
```

### SQL Queries

**❌ ALDRIG:**
```php
<?php
// ALDRIG direkt SQL med user input
$result = \Drupal::database()->query(
  "SELECT * FROM node WHERE title = '" . $user_input . "'"
);
```

**✅ ALLTID:**
```php
<?php
// EntityQuery (preferred)
$query = \Drupal::entityQuery('node')
  ->condition('title', $user_input)
  ->accessCheck(TRUE);
$ids = $query->execute();

// Eller parametriserade queries
$result = \Drupal::database()->query(
  "SELECT * FROM node WHERE title = :title",
  [':title' => $user_input]
);
```

---

## 📦 Composer Dependencies

**tritonled_custom/composer.json:**
```json
{
  "name": "drupal/tritonled_custom",
  "type": "drupal-custom-module",
  "description": "Custom functionality for TritonLED",
  "require": {
    "php": ">=8.2",
    "drupal/core": "^11.0",
    "drupal/commerce": "^2.0"
  },
  "require-dev": {
    "drupal/devel": "^5.0"
  }
}
```

---

## ✅ Code Review Checklist

Innan commit:

- [ ] Dependency Injection (EJ `\Drupal::`)
- [ ] Docblocks på alla funktioner
- [ ] Inline comments förklarar VARFÖR
- [ ] PSR-12 formatting
- [ ] XSS-säkert (escape/filter)
- [ ] SQL parametriserat (EntityQuery eller placeholders)
- [ ] Inga hårdkodade värden
- [ ] Services i .services.yml
- [ ] Event Subscribers registrerade

**Run:**
```bash
# Check coding standards (om phpcs installerat)
vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/

# Auto-fix
vendor/bin/phpcbf --standard=Drupal web/modules/custom/
```

---

**Version**: 1.0  
**Skapad**: 2025-01-10  
**Författare**: Stefan + Claude
