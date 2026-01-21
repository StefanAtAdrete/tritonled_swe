<?php

namespace Drupal\Tests\commerce_vado\Functional;

/**
 * Tests basic commerce_vado groups functionality.
 *
 * @group commerce_vado
 */
class VadoGroupsTest extends VadoBrowserTestBase {

  /**
   * Tests the created VADO group items are in the inline form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVadoGroupItemInlineForm() {
    $this->drupalGet('/admin/commerce/vado-groups');
    // Check group title and group widget.
    $this->assertSession()->pageTextContains('Group 1');
    $this->assertSession()->pageTextContains('Static list');

    $this->drupalGet('/admin/commerce/vado-groups/1/edit');
    // Check group title.
    $this->assertSession()->pageTextContains('Group 1');
    // Check overwritten group item title.
    $this->assertSession()->pageTextContains('Group Item 1');
    // Check original variation title is being passed.
    // @todo Test is not depending upon our populateTitle feature.
    $this->assertSession()->pageTextContains('Child Product 2');
    $this->assertSession()->pageTextContains('Child Product 3');
    $this->assertSession()->pageTextContains('Child Product 4');
    // Check child variation prices.
    $this->assertSession()->pageTextContains('$110.00');
    $this->assertSession()->pageTextContains('$210.00');
    $this->assertSession()->pageTextContains('$310.00');
    $this->assertSession()->pageTextContains('$410.00');
    // Check the discount labels.
    $this->assertSession()->pageTextContains('10% (Discount)');
    $this->assertSession()->pageTextContains('0% (Excluded)');
    $this->assertSession()->pageTextContains('10% (Markup)');
    // Check child variation sku's.
    $this->assertSession()->pageTextContains('CHILD-1');
    $this->assertSession()->pageTextContains('CHILD-2');
    $this->assertSession()->pageTextContains('CHILD-3');
    $this->assertSession()->pageTextContains('CHILD-4');

    // Now delete a product, and check the page again.
    $this->childProduct4->delete();
    $this->drupalGet('/admin/commerce/vado-groups');
    // Check group title and group widget.
    $this->assertSession()->pageTextContains('Group 1');
    $this->assertSession()->pageTextContains('Static list');
    $this->drupalGet('/admin/commerce/vado-groups/1/edit');
    // Check group title.
    $this->assertSession()->pageTextContains('Group 1');
    // Check overwritten group item title.
    $this->assertSession()->pageTextContains('Group Item 1');
    // Check original variation title is being passed.
    // @todo Test is not depending upon our populateTitle feature.
    $this->assertSession()->pageTextContains('Child Product 2');
    $this->assertSession()->pageTextContains('Child Product 3');
    // The group item title for the deleted product will be there,
    // but the price and SKU will not.
    $this->assertSession()->pageTextContains('Child Product 4');
    $this->assertSession()->pageTextNotContains('$410.00');
    $this->assertSession()->pageTextNotContains('CHILD-4');
  }

  /**
   * Tests that you can add static lists through the default add to cart form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testStaticGroup() {
    $this->assertEquals('Static list', $this->group->getGroupWidget()->getLabel());
    $this->parentVariation->set('child_variations', $this->childVariation->id());
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
    $this->cartManager->emptyCart($this->cart);
  }

  /**
   * Tests VADO behavior with static groups and unpublished variations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUnpublishedVariations() {
    // Check that unpublished variations is not set.
    $this->drupalGet('/admin/commerce/config/vado');
    $this->assertSession()->checkboxNotChecked('edit-settings-container-allow-unpublished-variations');
    // Attach the group to the parent.
    $this->parentVariation->set('variation_groups', $this->group->id());
    $this->parentVariation->save();
    // Unpublish the single variation.
    $this->childVariation1->setUnpublished();
    $this->childVariation1->save();

    // Add parent to cart with unpublished set to FALSE.
    // Child Product 1 should NOT show up.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(4, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextNotContains('Child Product 1');
    $this->assertSession()->pageTextContains('Child Product 2');
    $this->assertSession()->pageTextContains('Child Product 3');
    $this->assertSession()->pageTextContains('Child Product 4');
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
    // Now Child Product 1 should show up.
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(5, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextContains('Child Product 1');
    $this->assertSession()->pageTextContains('Child Product 2');
    $this->assertSession()->pageTextContains('Child Product 3');
    $this->assertSession()->pageTextContains('Child Product 4');
    $this->cartManager->emptyCart($this->cart);

    // Now delete the variation, and check the bundle.
    $this->childVariation1->delete();
    $this->drupalGet($this->parentProduct->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->orderStorage->resetCache([$this->cart->id()]);
    $this->cart = $this->orderStorage->load($this->cart->id());
    $this->assertCount(4, $this->cart->getItems());
    // Test the cart page.
    $this->drupalGet('cart');
    $this->assertSession()->pageTextContains('Parent Product');
    $this->assertSession()->pageTextContains('Child Product');
    $this->assertSession()->pageTextNotContains('Child Product 1');
    $this->assertSession()->pageTextContains('Child Product 2');
    $this->assertSession()->pageTextContains('Child Product 3');
    $this->assertSession()->pageTextContains('Child Product 4');
    $this->cartManager->emptyCart($this->cart);
  }

}
