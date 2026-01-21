<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the checkboxes vado group widget.
 *
 * @CommerceVadoGroupWidget(
 *   id = "checkboxes",
 *   label = @Translation("Checkboxes"),
 *   allows_multiple = TRUE,
 * )
 */
class Checkboxes extends VadoGroupWidgetBase {

  /**
   * {@inheritDoc}
   */
  public function buildElement(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $build = [
      '#type' => 'checkboxes',
      '#title' => $this->parentEntity->label(),
      '#title_display' => $config['group_title_display'],
      '#options' => $this->buildGroupItemOptions(),
      '#default_value' => $this->getDefaultValue(),
      '#required' => $this->parentEntity->isRequired(),
      '#empty_value' => $this->showEmptyOption() ? '' : NULL,
      '#ajax' => [
        'callback' => [get_class($form_state->getFormObject()), 'ajaxRefresh'],
        'wrapper' => $form['#wrapper_id'],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function showEmptyOption() {
    return FALSE;
  }

}
