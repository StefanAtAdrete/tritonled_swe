# Task 010b: commerce_variation_blocks — AJAX-uppdatering vid variantbyte

**Created**: 2026-02-28
**Status**: Planning
**Priority**: KRITISK
**Parent Task**: TASK-010
**Related**: web/modules/custom/commerce_variation_blocks/

---

## 1. PROBLEMET

Pseudo-fälten (Electrical, Mechanical, Certifications) renderas korrekt vid
sidladdning, men uppdateras INTE via AJAX när användaren byter variant.

**Orsak**: Commerce's AJAX-system (`ProductVariationFieldRenderer::replaceRenderedFields`)
skickar `ReplaceCommand` per individuellt fält via CSS-klassen:
`product--variation-field--variation_{field_name}__{product_id}`

Pseudo-fält på produktentiteten ingår inte i detta system. Commerce vet inte
om dem och skickar inga kommandon för dem.

---

## 2. LÖSNING

### Hook: hook_commerce_product_variation_field_injection()
Commerce exponerar denna hook för att låta moduler lägga till egna
AJAX-kommandon när en variant byts.

```php
function commerce_variation_blocks_commerce_product_variation_field_injection(
  AjaxResponse $response,
  ProductVariationInterface $variation,
  $view_mode
) {
  // För varje aktiv variation view mode:
  // 1. Rendera varianten med det view mode:t
  // 2. Skicka ReplaceCommand för pseudo-fältets container
}
```

### CSS-klass strategi
Pseudo-fält-containern behöver en unik, förutsägbar CSS-klass som
AJAX kan använda som selector. Förslag:
`commerce-variation-block--{view_mode_id}--{product_id}`

Denna klass sätts i `hook_entity_view()` på render arrayn,
och används som selector i `ReplaceCommand`.

### Alternativ: ReplaceCommand på container
Istället för att ersätta individuella fält ersätter vi hela
pseudo-fält-containern med ny rendering av view mode vid variantbyte.

---

## 3. IMPLEMENTATION

### Steg 1: Lägg till CSS-klass på pseudo-fält-containern
I `commerce_variation_blocks_entity_view()`:
```php
$build[$field_name]['#attributes']['class'][] = 
  'commerce-variation-block--' . $view_mode_id . '--' . $entity->id();
$build[$field_name]['#type'] = 'container'; // säkerställ wrapper
```

### Steg 2: Implementera AJAX-hooken
```php
function commerce_variation_blocks_commerce_product_variation_field_injection(
  AjaxResponse $response,
  ProductVariationInterface $variation,
  $view_mode
) {
  $view_modes = \Drupal::service('entity_display.repository')
    ->getViewModes('commerce_product_variation');
  
  $skip = ['default', 'cart', 'card', 'summary'];
  $view_builder = \Drupal::entityTypeManager()
    ->getViewBuilder('commerce_product_variation');
  
  foreach ($view_modes as $view_mode_id => $info) {
    if (in_array($view_mode_id, $skip)) continue;
    
    $product_id = $variation->getProductId();
    $selector = '.commerce-variation-block--' . $view_mode_id . '--' . $product_id;
    $rendered = $view_builder->view($variation, $view_mode_id);
    $rendered['#attributes']['class'][] = 
      'commerce-variation-block--' . $view_mode_id . '--' . $product_id;
    $rendered['#type'] = 'container';
    
    $response->addCommand(new ReplaceCommand($selector, $rendered));
  }
}
```

### Steg 3: Verifiera
- Byt variant på produktsidan
- Kontrollera att Voltage/Lumens uppdateras
- Kontrollera Network-tab: AJAX-response innehåller ReplaceCommand
- Inga JS-fel i console

---

## 4. FILER ATT ÄNDRA

- `web/modules/custom/commerce_variation_blocks/commerce_variation_blocks.module`

---

## 5. TESTRESULTAT

*Fylls i efter implementation*

- [ ] Voltage uppdateras vid variantbyte
- [ ] Lumens uppdateras vid variantbyte  
- [ ] Inga JS-fel
- [ ] Fungerar för alla view modes (Electrical, Mechanical, Certifications)
- [ ] Fungerar med Layout Builder aktivt på produkten

---

## 6. LÄRDOMAR

*Fylls i efter completion*
