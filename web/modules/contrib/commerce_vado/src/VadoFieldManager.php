<?php

namespace Drupal\commerce_vado;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;

/**
 * The vado field manager.
 */
class VadoFieldManager implements VadoFieldManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The vado config settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * VadoFieldInstaller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->config = $config_factory->getEditable('commerce_vado.settings');
  }

  /**
   * {@inheritDoc}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritDoc}
   */
  public function getVadoFields() {
    return [
      'child_variations' => [
        'label' => $this->t('Variation add-on children'),
        'description' => $this->t('Choose child variation(s) to be added to the cart with this parent.'),
        'field_type' => 'entity_reference',
        'settings' => [
          'target_type' => 'commerce_product_variation',
        ],
        'cardinality' => -1,
        'form_widget' => 'entity_reference_autocomplete',
        'weight' => 90,
        'primary_field' => TRUE,
      ],
      'variation_groups' => [
        'label' => $this->t('Variation add-on groups'),
        'description' => $this->t('Choose variation group(s) to be added to the cart with this parent.'),
        'field_type' => 'entity_reference',
        'settings' => [
          'target_type' => 'commerce_vado_group',
        ],
        'cardinality' => -1,
        'form_widget' => 'entity_reference_autocomplete',
        'weight' => 90,
        'primary_field' => TRUE,
      ],
      'sync_quantity' => [
        'label' => $this->t('Sync cart quantity'),
        'description' => $this->t('Lock the cart quantity of the selected child variation(s) to the parent quantity.'),
        'field_type' => 'boolean',
        'settings' => [],
        'cardinality' => 1,
        'form_widget' => 'boolean_checkbox',
        'weight' => 91,
        'primary_field' => FALSE,
        'dependencies' => [
          'child_variations',
          'variation_groups',
        ],
      ],
      'bundle_discount' => [
        'label' => $this->t('Bundle discount'),
        'description' => $this->t('Enter a percentage to discount the price of the child variation(s) or variation group(s) for this parent.'),
        'field_type' => 'decimal',
        'settings' => [
          'max' => 100,
          'suffix' => '%',
        ],
        'cardinality' => 1,
        'form_widget' => 'number',
        'weight' => 92,
        'primary_field' => FALSE,
        'dependencies' => [
          'child_variations',
          'variation_groups',
        ],
      ],
      'include_parent' => [
        'label' => $this->t('Include parent in discount'),
        'description' => $this->t('Include the parent variation in the bundle discount applied to the child variation(s) or variation group(s).'),
        'field_type' => 'boolean',
        'settings' => [],
        'cardinality' => 1,
        'form_widget' => 'boolean_checkbox',
        'weight' => 93,
        'primary_field' => FALSE,
        'dependencies' => [
          'child_variations',
          'variation_groups',
        ],
      ],
      'exclude_parent' => [
        'label' => $this->t('Exclude parent variation'),
        'description' => $this->t("Exclude the parent variation from the order when adding it's children to the cart. <br> Excluding the parent will cause sync quantity, include parent, and the bundle discount to be disregarded for that parent variation."),
        'field_type' => 'boolean',
        'settings' => [],
        'cardinality' => 1,
        'form_widget' => 'boolean_checkbox',
        'weight' => 91,
        'primary_field' => FALSE,
        'dependencies' => [
          'child_variations',
          'variation_groups',
        ],
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getPrimaryFields() {
    $primary_fields = [];
    foreach ($this->getVadoFields() as $field_name => $settings) {
      if ($settings['primary_field']) {
        $primary_fields[] = $field_name;
      }
    }

    return $primary_fields;
  }

  /**
   * {@inheritDoc}
   */
  public function isVadoEnabled(string $variation_type_id) {
    foreach ($this->getPrimaryFields() as $primary_field) {
      if ($this->hasField($primary_field, $variation_type_id)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function hasField(string $field_name, string $variation_type_id) {
    $has_field = FALSE;

    $definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $variation_type_id);
    if (isset($definitions[$field_name])) {
      $has_field = TRUE;
    }

    return $has_field;
  }

  /**
   * {@inheritDoc}
   */
  public function hasFieldData(string $field_name, string $variation_type_id) {
    $has_field_data = FALSE;

    if (!$this->hasField($field_name, $variation_type_id)) {
      return $has_field_data;
    }

    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $count = $variation_storage->getQuery()
      ->condition('type', $variation_type_id)
      ->exists($field_name)
      ->range(0, 1)
      ->count()
      ->accessCheck(FALSE)
      ->execute();

    if ($count > 0) {
      $has_field_data = TRUE;
    }

    return $has_field_data;
  }

  /**
   * {@inheritDoc}
   */
  public function installField(string $field_name, string $variation_type_id) {
    $vado_fields = $this->getVadoFields();
    $field = FieldConfig::create([
      'entity_type' => 'commerce_product_variation',
      'field_name' => $field_name,
      'bundle' => $variation_type_id,
      'label' => $vado_fields[$field_name]['label'],
      'description' => $vado_fields[$field_name]['description'],
      'settings' => $vado_fields[$field_name]['settings'],
    ]);
    $field->save();
  }

  /**
   * {@inheritDoc}
   */
  public function installDefaultFormDisplay(string $field_name, string $variation_type_id) {
    $vado_fields = $this->getVadoFields();

    $form_display_storage = $this->entityTypeManager->getStorage('entity_form_display');
    $form_display = $form_display_storage->load('commerce_product_variation.' . $variation_type_id . '.default');
    if (!$form_display) {
      $form_display = $form_display_storage->create([
        'status' => TRUE,
        'id' => 'commerce_product_variation.' . $variation_type_id . '.default',
        'targetEntityType' => 'commerce_product_variation',
        'bundle' => $variation_type_id,
        'mode' => 'default',
        'settings' => $vado_fields[$field_name]['settings'],
      ]);
    }
    // Set the form display for the variation type so new fields are visible.
    $form_display->setComponent($field_name, [
      'type' => $vado_fields[$field_name]['form_widget'],
      'weight' => $vado_fields[$field_name]['weight'],
      'settings' => $vado_fields[$field_name]['settings'],
    ])->save();
  }

  /**
   * {@inheritDoc}
   */
  public function updateFields(array $fields, string $variation_type_id) {
    $vado_fields = $this->getVadoFields();
    foreach ($fields as $field_name => $enabled) {
      if (!array_key_exists($field_name, $vado_fields)) {
        throw new \Exception(sprintf('The %field_name field is not a vado field', ['%field_name', $field_name]));
      }
      $field_config = FieldConfig::loadByName('commerce_product_variation', $variation_type_id, $field_name);
      // Delete enabled fields that are NOT installed on the variation type.
      if (!$enabled && $field_config) {
        $field_config->delete();
      }
      // Create enabled fields that are NOT installed on the variation type.
      elseif ($enabled && !$field_config) {
        $this->installField($field_name, $variation_type_id);
        $this->installDefaultFormDisplay($field_name, $variation_type_id);
      }
    }
  }

}
