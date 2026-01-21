<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the select list vado group widget.
 *
 * @CommerceVadoGroupWidget(
 *   id = "select_list_multiple",
 *   label = @Translation("Select list (Multiple)"),
 *   allows_multiple = TRUE,
 * )
 */
class SelectListMultiple extends SelectList {

  /**
   * {@inheritDoc}
   */
  public function buildElement(array $form, FormStateInterface $form_state) {
    $build = parent::buildElement($form, $form_state);
    $build['#multiple'] = TRUE;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function showEmptyOption() {
    return FALSE;
  }

}
