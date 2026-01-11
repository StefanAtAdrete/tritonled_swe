# Commerce AJAX Problem - LÖST ✅

**Problem löst**: 2025-01-08  
**Status**: Verifierad lösning  
**Relevans**: Commerce produkter med varianter

---

## 🔴 Problemet

Custom product templates (`commerce-product--full.html.twig`) **förstörde Commerce AJAX-funktionalitet** för produktvarianter.

### Symptom:

När användare valde en variant (t.ex. watt, CCT):
- ❌ Pris uppdaterades INTE
- ❌ Produktbilder uppdaterades INTE  
- ❌ Tillgänglighet (stock) uppdaterades INTE
- ❌ SKU visades INTE korrekt

AJAX-requesten funkade server-side (200 OK), men DOM uppdaterades inte.

---

## 🔍 Root Cause

Commerce AJAX-systemet förlitar sig på **specifik DOM-struktur**:

### Kräver:

1. **Specifika CSS-klasser** på containers:
```html
<div class="product--variation-field--variation_field_price__123">
  <!-- Price renders here -->
</div>
```

2. **Field injection** via JavaScript:
```javascript
// Commerce söker efter klasser som matchar:
.product--variation-field--variation_field_[FIELD_NAME]__[VARIATION_ID]
```

3. **Drupal's rendering system** intakt:
```php
// Content array måste innehålla Commerce's injected fields
{{ content.variations }}
{{ content.field_price }}
```

### Custom template bröt:

```twig
{# ❌ DETTA BRYTER AJAX #}
<div class="custom-price-container">
  <span>{{ variation.price.formatted }}</span>
</div>
{# Commerce kan inte hitta sin injection-punkt! #}
```

---

## ✅ Lösningen

### Använd INTE custom product templates

**Istället:**

#### 1. Layout Builder för struktur

```
Structure → Content types → Product → Manage Display
→ Enable Layout Builder
→ Allow per-content layouts (optional)
```

**Fördelar:**
- Bevara Commerce rendering
- Flexibel layout per produkt
- Inga templates att underhålla
- Commerce AJAX fungerar perfekt!

#### 2. Bootstrap Layout Builder för grid

```bash
composer require drupal/bootstrap_layout_builder
drush en bootstrap_layout_builder -y
```

**Använd:**
- Add Section → Bootstrap Grid
- Välj layout: 50/50, 33/67, 25/75, etc
- Dra fields (variations, images, price) in i kolumner

**Resultat**: Responsive grid **utan** att förstöra Commerce DOM.

#### 3. Field Groups för field-gruppering

```bash
composer require drupal/field_group
drush en field_group -y
```

**Användning:**
```
Manage Display → Add field group
→ Välj format: Fieldset, Details, Accordion
→ Dra fields in i grupp
→ CSS classes på grupp
```

**Fördelar:**
- Semantic gruppering av fields
- Bootstrap-klasser via UI
- Behåller Commerce rendering
- Accordion/tabs utan templates

**Exempel: Product Specifications**
```
Field Group: "Technical Specs" (Accordion)
├── Field: Wattage
├── Field: CCT  
├── Field: CRI
└── Field: IP Rating
```

#### 4. Event Subscriber för custom AJAX-beteende

**Om du MÅSTE ha custom AJAX-logik:**

**src/EventSubscriber/ProductVariationSubscriber.php:**
```php
<?php

namespace Drupal\tritonled_custom\EventSubscriber;

use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Responds to product variation AJAX changes.
 */
class ProductVariationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE => ['onVariationChange', -100],
    ];
  }

  /**
   * Adds custom data to AJAX response.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent $event
   *   The event.
   */
  public function onVariationChange(ProductVariationAjaxChangeEvent $event) {
    $response = $event->getResponse();
    $variation = $event->getProductVariation();
    
    // Example: Update SKU display
    $response->addCommand(new InvokeCommand(
      '.product-sku',
      'text',
      [$variation->getSku()]
    ));
    
    // Example: Update custom attribute display
    if ($variation->hasField('field_cct')) {
      $cct = $variation->get('field_cct')->value;
      $response->addCommand(new InvokeCommand(
        '.product-cct-value',
        'text',
        [$cct . 'K']
      ));
    }
  }

}
```

**tritonled_custom.services.yml:**
```yaml
services:
  tritonled_custom.product_variation_subscriber:
    class: Drupal\tritonled_custom\EventSubscriber\ProductVariationSubscriber
    tags:
      - { name: event_subscriber }
```

**Fördelar:**
- Commerce AJAX fungerar fortfarande
- Custom beteende VIA AJAX-systemet
- Inte ISTÄLLET FÖR AJAX-systemet

---

## 🧪 Testing

### Verifiera att lösningen fungerar:

#### 1. AJAX Response
```bash
# Öppna Browser DevTools (F12)
# Network tab
# Välj variant på produktsida
# Kolla AJAX request:

POST /product/123/ajax
Status: 200 OK
Response: JSON with updated fields
```

#### 2. DOM Update
```javascript
// Console check
$('.product--variation-field--variation_field_price__123').length > 0
// Should be true
```

#### 3. Visual Test
- [ ] Välj variant → Pris ändras omedelbart
- [ ] Välj variant → Bild ändras (om applicable)
- [ ] Välj variant → SKU uppdateras
- [ ] Välj variant → "Add to cart" button enabled/disabled baserat på stock

#### 4. Console Errors
```
# Console (F12) ska INTE visa:
❌ Uncaught TypeError: Cannot read property...
❌ AJAX error response

# Utan errors = funkar!
```

---

## 📋 Implementation Checklist

När du arbetar med Commerce produkter med varianter:

- [ ] Använd **INTE** custom `commerce-product--[type].html.twig`
- [ ] Använd **INTE** custom `commerce-product-variation--[type].html.twig`
- [ ] Använd Layout Builder för layout
- [ ] Använd Bootstrap Layout Builder för grids
- [ ] Använd Field Groups för field-gruppering
- [ ] CSS-klasser via Block Class / Field Group settings
- [ ] Custom AJAX beteende via Event Subscriber
- [ ] Testa AJAX efter varje ändring

---

## 🎓 Lärdomar

### Generell regel:

**Commerce templates = Danger Zone 🚫**

Commerce's rendering är **mycket** komplext:
- Field injection
- AJAX replacements
- Dynamic pricing
- Stock availability
- Variation switching

**Alla dessa bryts lätt av custom templates.**

### Safe zone:

✅ Layout Builder  
✅ Field Groups  
✅ Bootstrap classes via UI  
✅ Event Subscribers  
✅ Preprocess hooks (minimal)  

### Danger zone:

⚠️ Template overrides  
⚠️ Twig template suggestions  
⚠️ Hårdkodad HTML  

---

## 🔗 Relaterade Filer

- Beslutsträd: `/docs/01-decision-trees/commerce-decision-tree.md`
- Coding standards: `/docs/02-standards/coding-standards.md`
- Godkända moduler: `/docs/02-standards/approved-modules.md`

---

## 📚 Externa Referenser

**Commerce dokumentation:**
- https://docs.drupalcommerce.org/commerce2/developer-guide/products/displaying-products
- https://docs.drupalcommerce.org/commerce2/developer-guide/products/product-variations

**Issue queue:**
- https://www.drupal.org/project/commerce/issues (sök: "AJAX")

---

**Version**: 1.1  
**Skapad**: 2025-01-08  
**Uppdaterad**: 2025-01-11 (Display Suite ersatt med Field Groups)  
**Testad**: 2025-01-08  
**Verifierad**: ✅  
**Författare**: Stefan + Claude
