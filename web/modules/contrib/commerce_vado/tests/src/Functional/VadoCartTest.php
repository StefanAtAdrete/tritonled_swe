<?php

namespace Drupal\Tests\commerce_vado\Functional;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\views\Views;

/**
 * Tests commerce_vado cart features.
 *
 * @group commerce_vado
 */
class VadoCartTest extends VadoBrowserTestBase {

  /**
   * An array of child id's.
   *
   * @var array
   */
  protected $childIds;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set the prices to match the legacy test.
    $this->parentVariation->setPrice(new Price('0', 'USD'));
    $this->parentVariation->save();
    $this->parentVariation1->setPrice(new Price('0', 'USD'));
    $this->parentVariation1->save();
    $this->childVariation->setPrice(new Price('75.99', 'USD'));
    $this->childVariation->save();
    $this->childVariation1->setPrice(new Price('55.99', 'USD'));
    $this->childVariation1->save();

    $this->childIds = [
      $this->childVariation->id(),
      $this->childVariation1->id(),
      $this->childVariation1->id(),
    ];
  }

  /**
   * Tests VADO without quantity sync.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSyncQuantityOff() {

    // Add parent to cart before setting child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    // Test the cart page quantity exists.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->cartManager->emptyCart($this->cart);

    // Attach the children to the parent and ensure sync quantity is off.
    $this->parentVariation->set('child_variations', $this->childIds);
    $this->parentVariation->set('sync_quantity', FALSE);
    $this->parentVariation->save();
    // Add parent to cart after setting child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    // Note get items does not reflect total quantity, but order items.
    $this->assertCount(3, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);
    $this->assertSession()->buttonExists('edit-remove-button-0');
    $this->assertSession()->buttonExists('edit-remove-button-1');
    $this->assertSession()->buttonExists('edit-remove-button-2');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');

    // Edit the parent quantity to make sure the child does NOT sync.
    $values = [
      'edit_quantity[0]' => 2,
    ];
    $this->assertSession()->buttonExists('Update cart');
    $this->submitForm($values, 'Update cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);

    // Remove the parent and make sure it's child stays in the cart.
    $values = [
      'edit_quantity[0]' => 0,
    ];
    $this->submitForm($values, 'Update cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 2);
    $this->assertSession()->fieldNotExists('edit-edit-quantity-2');
    $this->assertSession()->pageTextNotContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');
    $this->assertSession()->pageTextContains(t('Your shopping cart has been updated.'));
  }

  /**
   * Tests VADO with quantity sync.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSyncQuantityOn() {

    // Add parent to cart before setting child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    // Test the cart page quantity exists.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->cartManager->emptyCart($this->cart);

    // Attach the child to the parent and ensure sync quantity is on.
    $this->parentVariation->set('child_variations', $this->childIds);
    $this->parentVariation->set('sync_quantity', TRUE);
    $this->parentVariation->save();
    // Add parent to cart after setting child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    // Note getItems doesn't reflect quantity, but # of order items.
    $this->assertCount(3, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);
    $this->assertSession()->buttonExists('edit-remove-button-0');

    // Test that the remove button is removed for the synced variation.
    $this->assertSession()->buttonNotExists('edit-remove-button-1');
    // Test that the quantity widget is disabled for synced the variation.
    $this->assertSession()->elementAttributeContains('css', 'input#edit-edit-quantity-1', 'disabled', 'disabled');
    $this->assertSession()->elementAttributeContains('css', 'input#edit-edit-quantity-2', 'disabled', 'disabled');
    // Test that the Remove button on the parent has changed to "Remove Bundle".
    $this->assertSession()->buttonExists('Remove Bundle');

    // Add the child by itself to make sure it doesn't combine.
    // This should create a new line item in the cart.
    $this->drupalGet($this->childProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    // Note get items does not reflect total quantity, but order items.
    $this->assertCount(4, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-3', 1);

    // Add parent to cart again to test quantities. This is different
    // than updating as it calls on the cartEntityAddEvent again.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(4, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 4);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-3', 1);

    // Update the parent quantity to make sure the child syncs.
    $values = [
      'edit_quantity[0]' => 3,
    ];
    $this->assertSession()->buttonExists('Update cart');
    $this->submitForm($values, 'Update cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 3);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 3);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 6);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-3', 1);
    $values = [
      'edit_quantity[0]' => 1,
    ];
    $this->submitForm($values, 'Update cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-3', 1);
    $this->assertSession()->elementTextContains('css', 'td.views-field.views-field-unit-price__number', '$0.00');
    $this->assertSession()->pageTextContains('$0.00');
    $this->assertSession()->pageTextContains('$75.99');
    $this->assertSession()->pageTextContains('$55.99');

    // Enable hide parent zero.
    $this->drupalGet('/admin/commerce/config/vado');
    $this->assertSession()->checkboxNotChecked('edit-settings-container-hide-parent-zero-price');
    $enable = [
      'settings_container[hide_parent_zero_price]' => TRUE,
    ];
    $this->submitForm($enable, 'Save');
    $this->assertSession()->checkboxChecked('edit-settings-container-hide-parent-zero-price');
    // We have to clear the view cache for hide parent zero.
    $view = Views::getView('commerce_cart_form');
    $view->storage->invalidateCaches();
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-3', 1);
    $this->assertSession()->elementTextNotContains('css', 'td.views-field.views-field-unit-price__number', '$0.00');
    $this->assertSession()->pageTextContains('$75.99');
    $this->assertSession()->pageTextContains('$55.99');

    // Remove the parent and make sure it's child is also removed.
    $values = [
      'edit_quantity[0]' => 0,
    ];
    $this->submitForm($values, 'Update cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldNotExists('edit-edit-quantity-1');
    $this->assertSession()->fieldNotExists('edit-edit-quantity-2');
    $this->assertSession()->fieldNotExists('edit-edit-quantity-3');

    // Remove the child that was added without the parent.
    $values = [
      'edit_quantity[0]' => 0,
    ];
    $this->submitForm($values, 'Update cart');
    $this->assertSession()->pageTextContains(t('Your shopping cart is empty.'));

    // Add parent to cart again to test quantity on cart form.
    $this->drupalGet($this->parentProduct->toUrl());
    $values = [
      'quantity[0][value]' => 10,
    ];
    $this->submitForm($values, 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(3, $this->cart->getItems());
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 10);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 10);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 20);
  }

  /**
   * Tests VADO exclude parent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testExcludeParent() {

    // Set the view display for the price to calculated.
    $variation_view_display = EntityViewDisplay::load('commerce_product_variation.default.default');
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

    // Add parent to cart before setting child.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(1, $this->cart->getItems());
    // Test the cart page quantity exists.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->cartManager->emptyCart($this->cart);

    // Attach the children to the parent and ensure exclude parent is on.
    $this->parentVariation->set('child_variations', $this->childIds);
    $this->parentVariation->set('exclude_parent', TRUE);

    // Set a new price to make sure the processor is excluding the parent price.
    $price = new Price('999.99', 'USD');
    $this->parentVariation->setPrice($price);
    $this->parentVariation->save();
    $this->drupalGet($this->parentProduct->toUrl());
    $this->assertSession()->pageTextContains('Price');
    $this->assertSession()->pageTextContains('$187.97');
    // The new price of 999.99 should NOT be included in the total.
    $this->assertSession()->pageTextNotContains('$1,187.96');

    // Add parent to cart after setting the children.
    $values = [
      'quantity[0][value]' => 1,
    ];
    $this->submitForm($values, 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    // Note get items does not reflect total quantity, but order items.
    // The count should be 2 not 3 because we excluded the parent.
    $this->assertCount(2, $this->cart->getItems());

    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '$75.99');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 2);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '$55.99');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '$111.98');
    $this->assertSession()->pageTextNotContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');
    $this->assertSession()->elementTextContains('css', 'span.order-total-line-value', '$187.97');

    // Attach the same children to different parent to make sure that
    // the combo id does not prevent the items from combining in the cart.
    $this->parentVariation1->set('child_variations', $this->childIds);
    $this->parentVariation1->set('exclude_parent', TRUE);
    $this->parentVariation1->save();
    $this->drupalGet($this->parentProduct1->toUrl());
    $this->submitForm([], 'Add to cart');
    // The count should still be 2.
    $this->assertCount(2, $this->cart->getItems());

    // Test the cart page and make sure the children were combined.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 2);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '$75.99');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '151.98');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 4);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '$55.99');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '223.96');
    $this->assertSession()->pageTextNotContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');
    $this->assertSession()->elementTextContains('css', 'span.order-total-line-value', '$375.94');

    // Attach the same children to different parent to make sure that
    // the combo id does not prevent the items from combining in the cart.
    $this->parentVariation1->set('child_variations', $this->childIds);
    $this->parentVariation1->set('exclude_parent', TRUE);
    $this->parentVariation1->save();
    $this->drupalGet($this->parentProduct1->toUrl());
    $values = [
      'quantity[0][value]' => 10,
    ];
    $this->submitForm($values, 'Add to cart');
    // The count should still be 2.
    $this->assertCount(2, $this->cart->getItems());

    // Test the cart page and make sure the children were combined.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 12);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '$75.99');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '911.88');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 24);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '$55.99');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '$1,343.76');
    $this->assertSession()->pageTextNotContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');
    $this->assertSession()->elementTextContains('css', 'span.order-total-line-value', '$2,255.64');
  }

  /**
   * Tests VADO behavior with unpublished variations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUnpublishedVariations() {
    // Check that unpublished variations is not set.
    $this->drupalGet('/admin/commerce/config/vado');
    $this->assertSession()->checkboxNotChecked('edit-settings-container-allow-unpublished-variations');
    // Attach the child to the parent and ensure sync quantity is on.
    $this->parentVariation->set('child_variations', $this->childIds);
    $this->parentVariation->save();
    // Unpublish the single variation.
    $this->childVariation->setUnpublished();
    $this->childVariation->save();

    // Add parent to cart with unpublished set to FALSE.
    // only the parent and childVariation1 should show up.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    // Note getItems doesn't reflect quantity, but # of order items.
    $this->assertCount(2, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 2);
    $this->cartManager->emptyCart($this->cart);

    // Enable unpublished variations.
    $this->drupalGet('/admin/commerce/config/vado');
    $this->assertSession()->checkboxNotChecked('edit-settings-container-allow-unpublished-variations');
    $enable = [
      'settings_container[allow_unpublished_variations]' => TRUE,
    ];
    $this->submitForm($enable, 'Save');
    $this->assertSession()->checkboxChecked('edit-settings-container-allow-unpublished-variations');

    // Add parent to cart after setting unpublished to TRUE.
    // Now both child variations should show up.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    // Note getItems doesn't reflect quantity, but # of order items.
    $this->assertCount(3, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-0', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-1', 1);
    $this->assertSession()->fieldValueEquals('edit-edit-quantity-2', 2);
    $this->cartManager->emptyCart($this->cart);

  }

}
