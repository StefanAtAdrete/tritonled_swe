<?php

namespace Drupal\Tests\commerce_vado\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests commerce_vado bundle discount field.
 *
 * @group commerce_vado
 */
class VadoDiscountsTest extends VadoBrowserTestBase {

  /**
   * Tests VADO bundle discount.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBundleDiscount() {
    // Attach the child to the parent and ensure the bundle discount is NULL.
    $this->parentVariation->set('child_variations', $this->childVariation->id());
    $this->parentVariation->set('bundle_discount', NULL);
    $this->parentVariation->set('include_parent', FALSE);
    $this->parentVariation->save();
    // Test that the order processor is combining the price of parent and child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->assertSession()->pageTextContains('Price');
    $this->assertSession()->pageTextContains('$175.99');
    // Test that the event subscriber did not apply a discount.
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('$100.00');
    $this->assertSession()->pageTextContains('$75.99');
    // Test the subtotal.
    $this->assertSession()->pageTextContains('$175.99');
    $this->cartManager->emptyCart($this->cart);

    // Attach the child to the parent and ensure the bundle discount is NULL.
    $this->parentVariation->set('child_variations', $this->childVariation->id());
    $this->parentVariation->set('bundle_discount', 5);
    $this->parentVariation->set('include_parent', TRUE);
    $this->parentVariation->save();
    // Test that the order processor is combining the price of parent and child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->assertSession()->pageTextContains('Price');
    $this->assertSession()->pageTextContains('$167.19');
    // Test that the event subscriber does apply a discount.
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(2, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('$95.00');
    $this->assertSession()->pageTextContains('$72.19');
    // Make sure it's getting rounded.
    $this->assertSession()->pageTextNotContains('$72.1905');
    // Test the subtotal.
    $this->assertSession()->pageTextContains('$167.19');
    $this->cartManager->emptyCart($this->cart);
  }

  /**
   * Tests VADO group and group item discounts.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGroupDiscounts() {
    // Switch to the Group add to cart form.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $product_view_display */
    $product_view_display = EntityViewDisplay::load('commerce_product.default.default');
    $product_view_display->setComponent('variations', [
      'type' => 'commerce_vado_group_add_to_cart',
      'region' => 'content',
      'label' => 'hidden',
      'settings' => [
        'combine' => TRUE,
      ],
    ]);
    $product_view_display->save();
    // Create the vado add to cart form display.
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.vado_group_add_to_cart');
    if (!$order_item_form_display) {
      $order_item_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'commerce_order_item',
        'bundle' => 'default',
        'mode' => 'vado_group_add_to_cart',
        'status' => TRUE,
      ]);
    }
    // Use the variation title for the vado add to cart form.
    $order_item_form_display->setComponent('purchased_entity', [
      'type' => 'commerce_product_variation_title',
    ]);
    $order_item_form_display->save();

    // Attach the child to the parent and ensure the bundle discount is NULL.
    $this->parentVariation->set('child_variations', $this->childVariation->id());
    $this->parentVariation->set('bundle_discount', NULL);
    $this->parentVariation->set('include_parent', FALSE);
    $this->parentVariation->save();
    // Attach the group to the parent.
    $this->parentVariation->set('variation_groups', $this->group->id());
    $this->parentVariation->save();
    // Test that the order processor is calculating the price.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->assertSession()->pageTextContains('$1,153.99');
    // Test that the event subscriber is calculating the prices.
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(6, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');
    $this->assertSession()->pageTextContains('Child Product 2');
    $this->assertSession()->pageTextContains('Child Product 3');
    $this->assertSession()->pageTextContains('Child Product 4');
    $this->assertSession()->pageTextContains('$100.00');
    $this->assertSession()->pageTextContains('$75.99');
    // Test the group item 1 discount of 10%.
    $this->assertSession()->pageTextContains('$99.00');
    // Test the group item 2 discount EXCLUSION of 0%.
    $this->assertSession()->pageTextContains('$210.00');
    // Test the group item 3 markup of 10%.
    $this->assertSession()->pageTextContains('$341.00');
    // Test the group discount of 20% passed to group item 4.
    $this->assertSession()->pageTextContains('$328.00');
    // Test the subtotal.
    $this->assertSession()->pageTextContains('$1,153.99');
    $this->cartManager->emptyCart($this->cart);

    // Change the group discount to empty.
    $this->group->set('group_discount', '');
    $this->group->save();
    // Set the bundle discount, and include the parent.
    $this->parentVariation->set('bundle_discount', 5);
    $this->parentVariation->set('include_parent', TRUE);
    $this->parentVariation->save();
    // Test that the order processor is calculating the price.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->assertSession()->pageTextContains('$1,206.69');
    // Test that the event subscriber is calculating the prices.
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(6, $this->cart->getItems());
    $this->drupalGet('cart');
    // Test the include parent bundle discount of 5%.
    $this->assertSession()->pageTextContains('$95.00');
    // Test the bundle discount of 5% gets passed to the child.
    $this->assertSession()->pageTextContains('$72.19');
    // Make sure it's getting rounded.
    $this->assertSession()->pageTextNotContains('$72.1905');
    // Test the group item 1 discount of 10%.
    $this->assertSession()->pageTextContains('$99.00');
    // Test the group item 2 discount EXCLUSION of 0%.
    $this->assertSession()->pageTextContains('$210.00');
    // Test the group item 3 markup of 10%.
    $this->assertSession()->pageTextContains('$341.00');
    // Test the bundle discount of 5% passed to group item 4.
    $this->assertSession()->pageTextContains('$389.50');
    // Test the subtotal.
    $this->assertSession()->pageTextContains('$1,206.69');
  }

}
