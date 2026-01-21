<?php

namespace Drupal\Tests\commerce_vado\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;
use Drupal\Tests\commerce_vado\Traits\VadoCreationTrait;

/**
 * Defines base class for commerce_vado test cases.
 *
 * @group commerce_vado
 */
class VadoBrowserTestBase extends CartBrowserTestBase {

  use VadoCreationTrait;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $parentVariation;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $parentVariation1;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $childVariation;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $childVariation1;


  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $parentProduct;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $parentProduct1;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $childProduct;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $childProduct1;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $childProduct2;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $childProduct3;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $childProduct4;

  /**
   * The group to test against.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroup
   */
  protected $group;


  /**
   * The order storage.
   *
   * @var \Drupal\commerce_order\OrderStorage
   */
  protected $orderStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_vado',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'access vado administration pages',
      'administer commerce_vado_group',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\commerce_vado\VadoFieldManagerInterface $vado_field_manager */
    $vado_field_manager = \Drupal::service('commerce_vado.field_manager');
    $vado_field_manager->installField('child_variations', 'default');
    $vado_field_manager->installField('variation_groups', 'default');
    $vado_field_manager->installField('bundle_discount', 'default');
    $vado_field_manager->installField('include_parent', 'default');
    $vado_field_manager->installField('sync_quantity', 'default');
    $vado_field_manager->installField('exclude_parent', 'default');
    // Test that the actual fields now exist on the variation type.
    $this->assertTrue($vado_field_manager->hasField('child_variations', 'default'));
    $this->assertTrue($vado_field_manager->hasField('variation_groups', 'default'));
    $this->assertTrue($vado_field_manager->hasField('bundle_discount', 'default'));
    $this->assertTrue($vado_field_manager->hasField('include_parent', 'default'));
    $this->assertTrue($vado_field_manager->hasField('sync_quantity', 'default'));
    $this->assertTrue($vado_field_manager->hasField('exclude_parent', 'default'));

    // Make sure the view display for the product is set to combine.
    $product_view_display = EntityViewDisplay::load('commerce_product.default.default');
    if (!$product_view_display) {
      $product_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product',
        'bundle' => 'default',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $product_view_display->setComponent('variations', [
      'type' => 'commerce_add_to_cart',
      'region' => 'content',
      'label' => 'hidden',
      'settings' => [
        'combine' => TRUE,
      ],
    ]);
    $product_view_display->save();

    // Set the view display for the price to calculated.
    $variation_view_display = EntityViewDisplay::load('commerce_product_variation.default.default');
    if (!$variation_view_display) {
      $variation_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product_variation',
        'bundle' => 'default',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $variation_view_display->setComponent('price', [
      'type' => 'commerce_price_calculated',
      'region' => 'content',
      'settings' => [
        'adjustment_types' => [
          'vado_discount' => 'vado_discount',
        ],
      ],
    ]);
    $variation_view_display->save();

    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('quantity', [
      'type' => 'number',
      'region' => 'content',
    ]);
    $order_item_form_display->save();

    $this->parentProduct = $this->createProductWithVariations(1, [
      'title' => 'Parent Product',
      'variations' => [
        [
          'sku' => 'PARENT',
          'price' => ['number' => '100.00'],
        ],
      ],
    ]);
    $this->parentProduct1 = $this->createProductWithVariations(1, [
      'title' => 'Parent Product',
      'variations' => [
        [
          'sku' => 'PARENT',
          'price' => ['number' => '100.00'],
        ],
      ],
    ]);
    $this->childProduct = $this->createProductWithVariations(1, [
      'title' => 'Child Product',
      'variations' => [
        [
          'title' => 'Child Product 1',
          'sku' => 'CHILD',
          'price' => ['number' => '75.99'],
        ],
      ],
    ]);
    $this->childProduct1 = $this->createProductWithVariations(1, [
      'title' => 'Child Product 1',
      'variations' => [
        [
          'sku' => 'CHILD-1',
          'price' => ['number' => '110.00'],
        ],
      ],
    ]);
    $this->childProduct2 = $this->createProductWithVariations(1, [
      'title' => 'Child Product 2',
      'variations' => [
        [
          'sku' => 'CHILD-2',
          'price' => ['number' => '210.00'],
        ],
      ],
    ]);
    $this->childProduct3 = $this->createProductWithVariations(1, [
      'title' => 'Child Product 3',
      'variations' => [
        [
          'sku' => 'CHILD-3',
          'price' => ['number' => '310.00'],
        ],
      ],
    ]);
    $this->childProduct4 = $this->createProductWithVariations(1, [
      'title' => 'Child Product 4',
      'variations' => [
        [
          'sku' => 'CHILD-4',
          'price' => ['number' => '410.00'],
        ],
      ],
    ]);
    $this->parentVariation = $this->parentProduct->getDefaultVariation();
    $this->parentVariation1 = $this->parentProduct1->getDefaultVariation();
    $this->childVariation = $this->childProduct->getDefaultVariation();
    $this->childVariation1 = $this->childProduct1->getDefaultVariation();

    $this->group = $this->createVadoGroupWithGroupItems(4, [
      'group_id' => 1,
      'title' => 'Group 1',
      'group_discount' => 20,
      'group_items' => [
        [
          'title' => 'Group Item 1',
          'variation' => $this->childProduct1->getDefaultVariation(),
          'group_item_discount' => 10,
        ],
        [
          'title' => $this->childProduct2->getDefaultVariation()->getTitle(),
          'variation' => $this->childProduct2->getDefaultVariation(),
          'group_item_discount' => 0,
        ],
        [
          'title' => $this->childProduct3->getDefaultVariation()->getTitle(),
          'variation' => $this->childProduct3->getDefaultVariation(),
          'group_item_discount' => -10,
        ],
        [
          'title' => $this->childProduct4->getDefaultVariation()->getTitle(),
          'variation' => $this->childProduct4->getDefaultVariation(),
          'group_item_discount' => '',
        ],
      ],
    ]);
    $this->orderStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
  }

}
