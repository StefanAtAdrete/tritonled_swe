<?php

namespace Drupal\commerce_vado\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the variation add-on condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_vado",
 *   label = @Translation("Exclude variation add-on"),
 *   display_label = @Translation("Exclude variation add-on"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemVado extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    if ($order_item->getData('commerce_vado_combo_id')) {
      return FALSE;
    }

    return TRUE;
  }

}
