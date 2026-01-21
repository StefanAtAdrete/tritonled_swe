<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the radios vado group widget.
 *
 * @CommerceVadoGroupWidget(
 *   id = "radios",
 *   label = @Translation("Radios"),
 * )
 */
class Radios extends VadoGroupWidgetBase {

  /**
   * {@inheritDoc}
   */
  public function buildElement(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $build = [
      '#type' => 'radios',
      '#title' => $this->parentEntity->label(),
      '#title_display' => $config['group_title_display'],
      '#options' => $this->buildGroupItemOptions(),
      '#default_value' => $this->getDefaultValue(),
      '#required' => $this->parentEntity->isRequired(),
      '#ajax' => [
        'callback' => [get_class($form_state->getFormObject()), 'ajaxRefresh'],
        'wrapper' => $form['#wrapper_id'],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  protected function shouldBuildEmptyOption() {
    return !$this->parentEntity->isRequired();
  }

}
