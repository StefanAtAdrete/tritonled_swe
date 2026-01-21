<?php

namespace Drupal\commerce_vado;

use Drupal\commerce_cart\OrderItemMatcher as OrderItemMatcherBase;
use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * The order item matcher.
 */
class OrderItemMatcher extends OrderItemMatcherBase {

  /**
   * {@inheritdoc}
   */
  public function matchAll(OrderItemInterface $order_item, array $order_items) {
    $matched_order_items = parent::matchAll($order_item, $order_items);

    $vado_combo_id = $order_item->getData('commerce_vado_combo_id');
    foreach ($matched_order_items as $key => $matched_order_item) {
      // Remove matches if they don't have the same combo ID.
      if ($matched_order_item->getData('commerce_vado_combo_id') !== $vado_combo_id) {
        unset($matched_order_items[$key]);
      }
      if (empty($matched_order_item->getAdjustments())) {
        // Make sure to use != and not !== as strict will always fail.
        // @todo remove after https://www.drupal.org/project/commerce/issues/3192850
        if ($order_item->getUnitPrice() != $matched_order_item->getUnitPrice()) {
          unset($matched_order_items[$key]);
        }
      }
    }

    // re-index the array after unsetting keys.
    return array_values($matched_order_items);
  }

}
