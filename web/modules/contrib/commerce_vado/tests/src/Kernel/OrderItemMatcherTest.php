<?php

namespace Drupal\Tests\commerce_vado\Kernel;

use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;
use Drupal\Tests\commerce_vado\Traits\VadoCreationTrait;

/**
 * Tests the order item matcher.
 *
 * @group commerce_vado
 */
class OrderItemMatcherTest extends CartKernelTestBase {

  use VadoCreationTrait;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_vado',
  ];

  /**
   * The vado field manager.
   *
   * @var \Drupal\commerce_vado\VadoFieldManagerInterface
   */
  protected $vadoFieldManager;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_vado_group');
    $this->installEntitySchema('commerce_vado_group_item');
    $this->installConfig(['commerce_vado']);

    $this->vadoFieldManager = $this->container->get('commerce_vado.field_manager');
    $this->vadoFieldManager->installField('child_variations', 'default');
    $this->vadoFieldManager->installField('variation_groups', 'default');
    $this->vadoFieldManager->installField('sync_quantity', 'default');

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * Tests child variations with sync quantity on.
   */
  public function testChildVariationsSyncQuantityOn() {
    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);

    $child_product = $this->createProductWithVariations(3);
    $parent_product = $this->createProductWithVariations(1, [
      'variations' => [
        [
          'sku' => 'PARENT',
          'price' => ['number' => '0.00'],
          'sync_quantity' => TRUE,
          'child_variations' => $child_product->getVariations(),
        ],
      ],
    ]);
    $parent_variation = $parent_product->getVariations()[0];
    $parent_order_item = $this->cartManager->createOrderItem($parent_variation);

    $this->cartManager->addOrderItem($cart, $parent_order_item);
    // Ensure all child variation order items are created with a quantity of 1.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('1', $order_item->getQuantity());
    }

    $this->cartManager->addEntity($cart, $parent_variation, 1);
    // Ensure all order items combine when adding the parent to the cart again.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('2', $order_item->getQuantity());
    }

    // Add the first child variation directly to the cart, not as a vado item.
    $this->cartManager->addEntity($cart, $child_product->variations->first()->entity);
    // With sync quantity, the child variation should not combine
    // with the variation created by vado.
    $this->assertCount(5, $cart->getItems());

    // Deleting the parent order item should delete all children.
    $parent_order_item->delete();
    // The child variation added directly to the cart should still be there.
    $this->assertCount(1, $cart->getItems());
  }

  /**
   * Tests child variations with sync quantity off.
   */
  public function testChildVariationsSyncQuantityOff() {
    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);

    $child_product = $this->createProductWithVariations(3);
    $parent_product = $this->createProductWithVariations(1, [
      'variations' => [
        [
          'sku' => 'PARENT',
          'price' => ['number' => '0.00'],
          'sync_quantity' => FALSE,
          'child_variations' => $child_product->getVariations(),
        ],
      ],
    ]);
    $parent_variation = $parent_product->getVariations()[0];
    $parent_order_item = $this->cartManager->createOrderItem($parent_variation);

    $this->cartManager->addOrderItem($cart, $parent_order_item);
    // Ensure all child variation order items are created with a quantity of 1.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('1', $order_item->getQuantity());
    }

    $this->cartManager->addEntity($cart, $parent_variation, 1);
    // Ensure all order items combine when adding the parent to the cart again.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('2', $order_item->getQuantity());
    }

    // @todo add this back in: https://www.drupal.org/project/commerce_vado/issues/3251430
    // // Add a child variation directly to the cart, not as a vado item.
    // $this->cartManager->addEntity($cart,
    // $child_product->variations->first()->entity);
    // // Without sync quantity, the child variation should
    // // combine with the variation created by vado.
    // $this->assertCount(4, $cart->getItems());
    // // Deleting the parent order item should NOT delete children.
    // $parent_order_item->delete();
    // $this->assertCount(3, $cart->getItems());
  }

  /**
   * Tests vado groups with sync quantity on.
   */
  public function testVadoGroupSyncQuantityOn() {
    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);

    $vado_group = $this->createVadoGroupWithGroupItems(3);
    $parent_product = $this->createProductWithVariations(1, [
      'variations' => [
        [
          'sku' => 'PARENT',
          'price' => ['number' => '0.00'],
          'sync_quantity' => TRUE,
          'variation_groups' => [$vado_group],
        ],
      ],
    ]);
    $parent_variation = $parent_product->getVariations()[0];

    $group_item_ids = array_map(function ($vado_group_item) {
      return $vado_group_item->id();
    }, $vado_group->getItems());

    $parent_order_item_1 = $this->cartManager->createOrderItem($parent_variation);
    // Simulate selecting the group items in the cart.
    $parent_order_item_1->setData('selected_addon_group_items', $group_item_ids);
    $parent_order_item_1->save();

    $this->cartManager->addOrderItem($cart, $parent_order_item_1);
    // Ensure all vado group item order items are created with a quantity of 1.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('1', $order_item->getQuantity());
    }

    $parent_order_item_2 = $this->cartManager->createOrderItem($parent_variation);
    // Simulate selecting the group items in the cart.
    $parent_order_item_2->setData('selected_addon_group_items', $group_item_ids);
    $parent_order_item_2->save();

    $this->cartManager->addOrderItem($cart, $parent_order_item_2);
    // Ensure all order items combine when adding the parent to the cart again.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('2', $order_item->getQuantity());
    }

    // Deleting the parent order item should NOT delete all children.
    $parent_order_item_1->delete();
    $this->assertEmpty($cart->getItems());
  }

  /**
   * Tests vado groups with sync quantity off.
   */
  public function testVadoGroupSyncQuantityOff() {
    $cart = $this->cartProvider->createCart('default', $this->store, $this->user);

    $vado_group = $this->createVadoGroupWithGroupItems(3);
    $parent_product = $this->createProductWithVariations(1, [
      'variations' => [
        [
          'sku' => 'PARENT',
          'price' => ['number' => '0.00'],
          'sync_quantity' => FALSE,
          'variation_groups' => [$vado_group],
        ],
      ],
    ]);
    $parent_variation = $parent_product->getVariations()[0];

    $group_item_ids = array_map(function ($vado_group_item) {
      return $vado_group_item->id();
    }, $vado_group->getItems());

    $parent_order_item_1 = $this->cartManager->createOrderItem($parent_variation);
    // Simulate selecting the group items in the cart.
    $parent_order_item_1->setData('selected_addon_group_items', $group_item_ids);
    $parent_order_item_1->save();

    $this->cartManager->addOrderItem($cart, $parent_order_item_1);
    // Ensure all vado group item order items are created with a quantity of 1.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('1', $order_item->getQuantity());
    }

    $parent_order_item_2 = $this->cartManager->createOrderItem($parent_variation);
    // Simulate selecting the group items in the cart.
    $parent_order_item_2->setData('selected_addon_group_items', $group_item_ids);
    $parent_order_item_2->save();

    $this->cartManager->addOrderItem($cart, $parent_order_item_2);
    // Ensure all order items combine when adding the parent to the cart again.
    $this->assertCount(4, $cart->getItems());
    foreach ($cart->getItems() as $order_item) {
      $this->assertEquals('2', $order_item->getQuantity());
    }

    // Deleting the parent order item should NOT delete all children.
    $parent_order_item_1->delete();
    $this->assertCount(3, $cart->getItems());
  }

}
