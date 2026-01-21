<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\commerce\Plugin\Commerce\Condition\ParentEntityAwareInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the base interface for vado group widgets.
 */
interface VadoGroupWidgetInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface, ParentEntityAwareInterface {

  /**
   * Gets the vado group widget label.
   *
   * @return mixed
   *   The vado group widget label.
   */
  public function getLabel();

  /**
   * Gets whether the vado group widget allows multiple group item selections.
   *
   * @return bool
   *   TRUE if the vado group widget allows multiple, FALSE otherwise.
   */
  public function allowsMultiple();

  /**
   * Builds the group widget.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The group widget.
   */
  public function buildElement(array $form, FormStateInterface $form_state);

  /**
   * Gets the default value for the widget.
   *
   * @return mixed
   *   The default value.
   */
  public function getDefaultValue();

  /**
   * Sets the selected product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation
   *   The selected product variation.
   *
   * @return $this
   */
  public function setSelectedVariation(ProductVariationInterface $selected_variation);

}
