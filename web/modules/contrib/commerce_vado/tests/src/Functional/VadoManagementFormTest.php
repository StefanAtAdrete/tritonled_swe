<?php

namespace Drupal\Tests\commerce_vado\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the VadoManagementForm.
 *
 * @group commerce_vado
 */
class VadoManagementFormTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_vado',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'access vado administration pages',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests the vado config management form.
   */
  public function testVadoManagementForm() {
    /** @var \Drupal\commerce_vado\VadoFieldManagerInterface $vado_field_manager */
    $vado_field_manager = \Drupal::service('commerce_vado.field_manager');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $this->drupalGet('/admin/commerce/config/vado');

    // Test the page title.
    $this->assertSession()->pageTextContains('Add on product variation types');

    // Test vado fields are all disabled.
    $this->assertSession()->checkboxNotChecked('edit-variation-types-default-fields-child-variations');
    $this->assertSession()->checkboxNotChecked('edit-variation-types-default-fields-variation-groups');
    $this->assertSession()->checkboxNotChecked('edit-variation-types-default-fields-sync-quantity');
    $this->assertSession()->checkboxNotChecked('edit-variation-types-default-fields-bundle-discount');
    $this->assertSession()->checkboxNotChecked('edit-variation-types-default-fields-include-parent');
    $this->assertSession()->checkboxNotChecked('edit-variation-types-default-fields-exclude-parent');
    $this->assertSession()->checkboxNotChecked('edit-settings-container-hide-parent-zero-price');
    $this->assertSession()->checkboxNotChecked('edit-settings-container-allow-unpublished-variations');

    // Tests the actual field config doesn't exist.
    foreach ($vado_field_manager->getVadoFields() as $field_name => $settings) {
      $this->assertFalse($vado_field_manager->hasField($field_name, 'default'));
    }

    // Enable all of the vado fields for default variation type.
    $edit = [
      'variation_types[default][fields][child_variations]' => TRUE,
      'variation_types[default][fields][variation_groups]' => TRUE,
      'variation_types[default][fields][sync_quantity]' => TRUE,
      'variation_types[default][fields][bundle_discount]' => TRUE,
      'variation_types[default][fields][include_parent]' => TRUE,
      'variation_types[default][fields][exclude_parent]' => TRUE,
      'settings_container[hide_parent_zero_price]' => TRUE,
      'settings_container[allow_unpublished_variations]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $entity_field_manager->clearCachedFieldDefinitions();

    // Test the status message.
    $this->assertSession()->pageTextContains('The configuration has been updated.');

    // Test vado fields are all enabled.
    $this->assertSession()->checkboxChecked('edit-variation-types-default-fields-child-variations');
    $this->assertSession()->checkboxChecked('edit-variation-types-default-fields-variation-groups');
    $this->assertSession()->checkboxChecked('edit-variation-types-default-fields-sync-quantity');
    $this->assertSession()->checkboxChecked('edit-variation-types-default-fields-bundle-discount');
    $this->assertSession()->checkboxChecked('edit-variation-types-default-fields-include-parent');
    $this->assertSession()->checkboxChecked('edit-variation-types-default-fields-exclude-parent');
    $this->assertSession()->checkboxChecked('edit-settings-container-hide-parent-zero-price');
    $this->assertSession()->checkboxChecked('edit-settings-container-allow-unpublished-variations');

    // Tests the actual field config now exists.
    foreach ($vado_field_manager->getVadoFields() as $field_name => $settings) {
      $this->assertTrue($vado_field_manager->hasField($field_name, 'default'));
    }
  }

}
