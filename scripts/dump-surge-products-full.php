<?php
for ($id = 27; $id <= 34; $id++) {
  $product = \Drupal\commerce_product\Entity\Product::load($id);
  echo '=== ' . $product->getTitle() . ' (ID:' . $id . ') ===' . PHP_EOL;
  echo 'field_brand: ' . $product->get('field_brand')->value . PHP_EOL;
  echo 'field_current_type: ' . $product->get('field_current_type')->value . PHP_EOL;
  echo 'field_spd_type: ' . $product->get('field_spd_type')->value . PHP_EOL;
  $media = $product->get('field_product_media')->getValue();
  echo 'field_product_media MID: ' . ($media[0]['target_id'] ?? 'none') . PHP_EOL;
  echo 'Variations: ' . PHP_EOL;
  foreach ($product->getVariations() as $v) {
    echo '  SKU: ' . $v->getSku() . PHP_EOL;
  }
  echo PHP_EOL;
}
