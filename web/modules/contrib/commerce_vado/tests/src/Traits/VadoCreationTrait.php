<?php

namespace Drupal\Tests\commerce_vado\Traits;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_vado\Entity\VadoGroup;
use Drupal\commerce_vado\Entity\VadoGroupInterface;
use Drupal\commerce_vado\Entity\VadoGroupItem;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines a trait for creating vado entities in tests.
 */
trait VadoCreationTrait {

  /**
   * Creates a vado group with vado group items.
   *
   * @param int $group_item_count
   *   The number of vado group items to create for the vado group.
   * @param array $vado_group_data
   *   The data to populate the vado group.
   *
   * @return \Drupal\commerce_vado\Entity\VadoGroupInterface
   *   The vado group.
   */
  protected function createVadoGroupWithGroupItems(int $group_item_count, array $vado_group_data = []) {
    $defaults = [
      'title' => $this->randomString(),
      'group_widget' => [
        'target_plugin_id' => 'static_list',
      ],
    ];
    $vado_group_data = NestedArray::mergeDeep($defaults, $vado_group_data);

    $group_item_data = [];
    while ($group_item_count >= 1) {
      // If group item data was provided with the group, use it.
      $data = $vado_group_data['group_items'][$group_item_count - 1] ?? [];
      $group_item_data[] = $this->generateVadoGroupItemData($data);
      $group_item_count--;
    }
    // Ensure group items is empty since they are created later.
    $vado_group_data['group_items'] = NULL;
    $vado_group = VadoGroup::create($vado_group_data);

    return $this->populateGroupItems($vado_group, $group_item_data);
  }

  /**
   * Populates a vado group with vado group items.
   *
   * @param \Drupal\commerce_vado\Entity\VadoGroupInterface $vado_group
   *   The vado group to populate with vado group items.
   * @param array $vado_group_item_data
   *   The data to generate the group items.
   *
   * @return \Drupal\commerce_vado\Entity\VadoGroupInterface
   *   The vado group with populated vado group items.
   */
  protected function populateGroupItems(VadoGroupInterface $vado_group, array $vado_group_item_data) {
    $vado_group_items = [];
    foreach ($vado_group_item_data as $data) {
      $data = $this->generateVadoGroupItemData($data);
      $group_item = VadoGroupItem::create($data);
      $group_item->save();
      $vado_group_items[$group_item->id()] = $group_item;
    }

    $vado_group->group_items = $vado_group_items;
    $vado_group->save();

    return $vado_group;
  }

  /**
   * Creates a product with product variations.
   *
   * @param int $variation_count
   *   The number of variations to create for the product.
   * @param array $product_data
   *   The data to populate the product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product.
   */
  protected function createProductWithVariations(int $variation_count, array $product_data = []) {
    $defaults = [
      'type' => 'default',
      'title' => $this->randomString(),
      'stores' => [$this->store],
    ];
    $product_data = NestedArray::mergeDeep($defaults, $product_data);

    $variation_data = [];
    while ($variation_count >= 1) {
      // If variation data was provided with the product, use it.
      $data = $product_data['variations'][$variation_count - 1] ?? [];
      $variation_data[] = $this->generateVariationData($data);
      $variation_count--;
    }
    // Ensure group items is empty since they are created later.
    $product_data['variations'] = NULL;
    $product = Product::create($product_data);

    return $this->populateVariations($product, $variation_data);
  }

  /**
   * Populates a product with product variations.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product to populate with variations.
   * @param array $variation_data
   *   The data to generate the variations.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product with populated variations.
   */
  protected function populateVariations(ProductInterface $product, array $variation_data) {
    $variations = [];
    foreach ($variation_data as $data) {
      $data = $this->generateVariationData($data);
      $variation = ProductVariation::create($data);
      $variation->save();
      $variations[$variation->id()] = $variation;
    }

    $product->setVariations($variations);
    $product->save();

    return $product;
  }

  /**
   * Creates a data array to generate product variations.
   *
   * @param array $variation_data
   *   The variation data.
   *
   * @return array
   *   The generated variation data.
   */
  protected function generateVariationData(array $variation_data = []) {
    $defaults = [
      'type' => 'default',
      'price' => [
        'currency_code' => 'USD',
      ],
    ];
    $variation_data = NestedArray::mergeDeep($defaults, $variation_data);

    if (!isset($variation_data['price']['number'])) {
      $variation_data['price']['number'] = (string) rand(1, 1000);
    }
    if (!isset($variation_data['sku'])) {
      $variation_data['sku'] = $this->randomString();
    }

    return $variation_data;
  }

  /**
   * Creates a data array to generate vado group items.
   *
   * @param array $vado_group_item_data
   *   The vado group item data.
   *
   * @return array
   *   The generated vado group item data.
   */
  protected function generateVadoGroupItemData(array $vado_group_item_data = []) {
    if (empty($vado_group_item_data['variation'])) {
      $product = $this->createProductWithVariations(1);
      $variation = $product->getVariations()[0];
      $vado_group_item_data['variation'] = $variation;
    }
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $vado_group_item_data['variation'];

    if (!isset($vado_group_item_data['title'])) {
      $vado_group_item_data['title'] = $variation->getTitle();
    }

    return $vado_group_item_data;
  }

}
