<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\commerce\EntityHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the static list vado group widget.
 *
 * @CommerceVadoGroupWidget(
 *   id = "static_list",
 *   label = @Translation("Static list"),
 *   allows_multiple = TRUE,
 * )
 */
class StaticList extends VadoGroupWidgetBase {

  /**
   * {@inheritDoc}
   */
  public function buildElement(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $build = [];
    $allow_unpublished = $this->allowsUnpublishedVariations();
    foreach ($this->parentEntity->getItems() as $group_item) {
      // If the group item doesn't have a variation entity,
      // or if the settings don't allow unpublished variations, continue.
      if (!$group_item->getVariation() || !$allow_unpublished && !$group_item->getVariation()->isPublished()) {
        continue;
      }
      $build['selections'][$group_item->id()] = [
        '#type' => 'hidden',
        '#value' => (string) $group_item->id(),
      ];
    }
    $build['display'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $this->buildGroupItemOptions(),
      '#attributes' => ['class' => 'addon_static_list'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    if ($config['group_title_display'] == 'before') {
      $build['display']['#title'] = $this->parentEntity->label();
    }

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultValue() {
    return EntityHelper::extractIds($this->parentEntity->getItems());
  }

  /**
   * {@inheritDoc}
   */
  protected function showEmptyOption() {
    return FALSE;
  }

}
