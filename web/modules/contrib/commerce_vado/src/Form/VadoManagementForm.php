<?php

namespace Drupal\commerce_vado\Form;

use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\commerce_vado\VadoFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic settings form for Commerce VADO.
 *
 * Provides configuration form to toggle fields on product variation types.
 *
 * Class VadoManagementForm
 *
 * @package Drupal\commerce_vado\Form
 */
class VadoManagementForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The vado field manager.
   *
   * @var \Drupal\commerce_vado\VadoFieldManagerInterface
   */
  protected $vadoFieldManager;

  /**
   * VadoManagementForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\commerce_vado\VadoFieldManagerInterface $vado_field_manager
   *   The vado field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, EntityTypeManagerInterface $entity_type_manager, Messenger $messenger, VadoFieldManagerInterface $vado_field_manager) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->vadoFieldManager = $vado_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('commerce_vado.field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vado_management_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_vado.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $wrapper = 'product_variations';
    $form['#prefix'] = '<div id="' . $wrapper . '">';
    $form['#suffix'] = '</div>';
    $config = $this->vadoFieldManager->getConfig();

    $form['#tree'] = TRUE;

    $form['variation_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Variation types'),
      '#open' => TRUE,
      'warning' => ['#markup' => $this->t('<strong>WARNING:</strong> Unchecking a box and saving the form will cause all data for the field to be deleted!')],
    ];

    $variation_type_storage = $this->entityTypeManager->getStorage('commerce_product_variation_type');
    // Load & format product variation types for checkboxes.
    $variation_types = $variation_type_storage->loadMultiple();
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    foreach ($variation_types as $variation_type) {
      $form['variation_types'][$variation_type->id()] = [
        '#type' => 'details',
        '#title' => $variation_type->label(),
        '#open' => $this->vadoFieldManager->isVadoEnabled($variation_type->id()),
        'fields' => $this->buildVadoFieldCheckboxElements($variation_type),
      ];
    }

    $form['settings_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => TRUE,
    ];

    $form['settings_container']['hide_parent_zero_price'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide zero price'),
      '#description' => $this->t('Hide parent variation price in cart and order views when set to zero.'),
      '#default_value' => $config->get('hide_parent_zero_price'),
      '#ajax' => [
        'callback' => [$this, 'ajaxWarning'],
        'wrapper' => $wrapper,
      ],
    ];

    $form['settings_container']['allow_unpublished_variations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow unpublished variations'),
      '#description' => $this->t('Allow unpublished child variations to be added to orders.'),
      '#default_value' => $config->get('allow_unpublished_variations'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Ajax callback for hide_parent_zero_price element.
   */
  public function ajaxWarning(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && end($triggering_element['#parents']) == 'hide_parent_zero_price') {
      $this->messenger->addWarning($this->t('You must clear the caches for this change to take effect.'));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

    // A variation type is considered enabled if a primary fields is enabled.
    $enabled_variation_types = [];
    foreach ($values['variation_types'] as $variation_type_id => $settings) {
      foreach ($this->vadoFieldManager->getPrimaryFields() as $primary_field) {
        $is_enabled = (bool) $settings['fields'][$primary_field];
        if ($is_enabled) {
          $enabled_variation_types[] = $variation_type_id;
          continue 2;
        }
      }
    }

    // If a variation type is not enabled, ensure all fields are disabled.
    foreach ($values['variation_types'] as $variation_type_id => $settings) {
      if (!in_array($variation_type_id, $enabled_variation_types)) {
        foreach ($settings['fields'] as $field_name => $value) {
          $form_state->setValueForElement($form['variation_types'][$variation_type_id]['fields'][$field_name], 0);
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['variation_types'] as $variation_type_id => $settings) {
      $this->vadoFieldManager->updateFields($settings['fields'], $variation_type_id);
    }

    $config = $this->vadoFieldManager->getConfig();
    $config->set('hide_parent_zero_price', $values['settings_container']['hide_parent_zero_price']);
    $config->set('allow_unpublished_variations', $values['settings_container']['allow_unpublished_variations']);
    $config->save();

    $this->messenger()->addMessage($this->t('The configuration has been updated.'));
  }

  /**
   * Builds the checkbox elements for each vado field for that variation type.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   *
   * @return array
   *   The checkbox elements.
   */
  protected function buildVadoFieldCheckboxElements(ProductVariationTypeInterface $variation_type) {
    $build = [];

    $vado_fields = $this->vadoFieldManager->getVadoFields();
    foreach ($vado_fields as $field_name => $settings) {
      $build[$field_name] = [
        '#type' => 'checkbox',
        '#title' => $settings['label'],
        '#description' => $settings['description'],
        '#default_value' => $this->vadoFieldManager->hasField($field_name, $variation_type->id()),
        '#disabled' => $this->shouldDisableFieldCheckbox($field_name, $variation_type),
      ];

      // Fields with dependencies should not be visible
      // unless a dependency is met.
      if (!empty($settings['dependencies'])) {
        foreach ($settings['dependencies'] as $dependent_field_name) {
          $selector = ':input[name="variation_types[' . $variation_type->id() . '][fields][' . $dependent_field_name . ']"]';
          $build[$field_name]['#states']['visible'][] = [$selector => ['checked' => TRUE]];
        }
      }
    }

    return $build;
  }

  /**
   * Determines if a field checkbox should be disabled for that variation type.
   *
   * Primary fields should be protected so that if they have data
   * they cannot be uninstalled.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   *
   * @return bool
   *   TRUE if checkbox should be disabled, FALSE otherwise.
   */
  protected function shouldDisableFieldCheckbox($field_name, ProductVariationTypeInterface $variation_type) {
    $primary_fields = $this->vadoFieldManager->getPrimaryFields();
    return $this->vadoFieldManager->hasFieldData($field_name, $variation_type->id()) && in_array($field_name, $primary_fields);
  }

}
