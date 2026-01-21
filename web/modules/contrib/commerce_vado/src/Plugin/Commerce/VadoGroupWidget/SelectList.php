<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the select list vado group widget.
 *
 * @CommerceVadoGroupWidget(
 *   id = "select_list",
 *   label = @Translation("Select list"),
 * )
 */
class SelectList extends VadoGroupWidgetBase {

  /**
   * {@inheritDoc}
   */
  public function buildElement(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $build = [
      '#type' => 'select',
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
   * {@inheritDoc}
   */
  protected function buildGroupTitleDisplayOptions() {
    return [
      'before' => $this->t('Before'),
      'after' => $this->t('After'),
      'invisible' => $this->t('Invisible'),
    ];
  }

}
