<?php

namespace Drupal\commerce_vado\EventSubscriber;

use Drupal\commerce\Context;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\OrderItemMatcherInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_vado\VadoFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to cartEntityAddEvent to add add-on's to an order.
 */
class VadoEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The vado field manager.
   *
   * @var \Drupal\commerce_vado\VadoFieldManagerInterface
   */
  protected $vadoFieldManager;

  /**
   * The order item matcher.
   *
   * @var \Drupal\commerce_cart\OrderItemMatcherInterface
   */
  protected $orderItemMatcher;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   * @param \Drupal\commerce_vado\VadoFieldManagerInterface $vado_field_manager
   *   The vado field manager.
   * @param \Drupal\commerce_cart\OrderItemMatcherInterface $order_item_matcher
   *   The order item matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Configuration Factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RounderInterface $rounder, ChainPriceResolverInterface $chain_price_resolver, VadoFieldManagerInterface $vado_field_manager, OrderItemMatcherInterface $order_item_matcher, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rounder = $rounder;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->vadoFieldManager = $vado_field_manager;
    $this->orderItemMatcher = $order_item_matcher;
    $this->configFactory = $configFactory;
  }

  /**
   * Get subscribed events.
   *
   * @return array
   *   The subscribed events.
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CART_ENTITY_ADD => ['cartEntityAddEvent', -99],
      OrderEvents::ORDER_PRESAVE => ['orderPreSaveEvent', -99],
      OrderEvents::ORDER_ITEM_DELETE => ['orderItemDeleteEvent', -99],
    ];
    return $events;
  }

  /**
   * Create child order items from referenced child variations and group items.
   */
  public function cartEntityAddEvent(CartEntityAddEvent $event) {
    $order_item = $event->getOrderItem();
    $order = $event->getCart();
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $parent_variation */
    $parent_variation = $order_item->getPurchasedEntity();

    if (!$this->vadoFieldManager->isVadoEnabled($parent_variation->bundle())) {
      return;
    }

    $sync_quantity = FALSE;
    if ($parent_variation->hasField('sync_quantity') && !$parent_variation->get('sync_quantity')->isEmpty()) {
      $sync_quantity = $parent_variation->get('sync_quantity')->value;
    }

    // Add logic to delete parent product when it's excluded.
    $exclude_parent = FALSE;
    if ($parent_variation->hasField('exclude_parent') && !$parent_variation->get('exclude_parent')->isEmpty()) {
      $exclude_parent = $parent_variation->get('exclude_parent')->value;
      // Set data the parent if the parent has exclude_parent on.
      if ($exclude_parent) {
        $order_item->setData('commerce_vado_exclude_parent', TRUE);
      }
    }

    $child_order_items = $order_item->getData('commerce_vado_child_order_items', []);
    // Only create add-on items if sync quantity is OFF,
    // or the order item doesn't already have children.
    if (!$sync_quantity || empty($child_order_items)) {

      // Create a multiplier value from the bundle discount integer value.
      $bundle_discount_integer = 0;
      if ($parent_variation->hasField('bundle_discount') && !$parent_variation->get('bundle_discount')->isEmpty()) {
        $bundle_discount_integer = $parent_variation->get('bundle_discount')->value;
      }
      $bundle_discount_multiplier = (100 - $bundle_discount_integer) / 100;

      $context = new Context($order->getCustomer(), $order->getStore(), NULL, [
        'commerce_vado_parent_variation' => $parent_variation,
      ]);

      // Get the setting for how to handle unpublished variations.
      $allow_unpublished = $this->configFactory->get('commerce_vado.settings')->get('allow_unpublished_variations');

      $addon_data = [];
      if ($parent_variation->hasField('child_variations') && !$parent_variation->get('child_variations')->isEmpty()) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $addon_variation */
        foreach ($parent_variation->get('child_variations')->referencedEntities() as $addon_variation) {
          // If the add-on isn't allowed to be added when unpublished, skip it.
          if (!$allow_unpublished && !$addon_variation->isPublished()) {
            continue;
          }
          $addon_price = $this->chainPriceResolver->resolve($addon_variation, 1, $context);
          $addon_price = $addon_price->multiply($bundle_discount_multiplier);
          $addon_key = $addon_variation->id() . '_' . $addon_price->getNumber();
          if (array_key_exists($addon_key, $addon_data)) {
            $addon_data[$addon_key]['quantity']++;
          }
          else {
            $addon_data[$addon_key] = [
              'variation' => $addon_variation,
              'discount_multiplier' => $bundle_discount_multiplier,
              'quantity' => 1,
              'price' => $addon_price,
            ];
          }
        }
      }

      $selected_group_items = $order_item->getData('selected_addon_group_items', FALSE);
      if ($selected_group_items) {
        $vado_group_item_storage = $this->entityTypeManager->getStorage('commerce_vado_group_item');
        /** @var \Drupal\commerce_vado\Entity\VadoGroupItemInterface[] $group_items */
        $group_items = $vado_group_item_storage->loadMultiple($selected_group_items);
        if (!empty($group_items)) {
          foreach ($group_items as $group_item) {
            // If the group item doesn't have a variation entity,
            // or if the settings don't allow unpublished variations, continue.
            if (!$group_item->getVariation() || !$allow_unpublished && !$group_item->getVariation()->isPublished()) {
              continue;
            }
            // Use the group_discount on the group item if available.
            // If not, use the bundle_discount.
            $group_discount_multiplier = $bundle_discount_multiplier;
            $group = $group_item->getGroup();
            if ($group->hasGroupDiscount()) {
              $group_discount_multiplier = $group->getGroupDiscountMultiplier();
            }
            // Use the group_item_discount on the group item if available.
            // If not, use the group_discount.
            $group_item_discount_multiplier = $group_discount_multiplier;
            if ($group_item->hasGroupItemDiscount()) {
              $group_item_discount_multiplier = $group_item->getGroupItemDiscountMultiplier();
            }
            $context = new Context($order->getCustomer(), $order->getStore(), NULL, [
              'commerce_vado_parent_variation' => $parent_variation,
              'commerce_vado_group_item' => $group_item,
            ]);
            $group_item_variation = $group_item->getVariation();
            $addon_price = $this->chainPriceResolver->resolve($group_item_variation, 1, $context);
            $addon_price = $addon_price->multiply($group_item_discount_multiplier);
            $addon_key = $group_item_variation->id() . '_' . $addon_price->getNumber();
            if (array_key_exists($addon_key, $addon_data)) {
              $addon_data[$addon_key]['quantity']++;
            }
            else {
              $addon_data[$addon_key] = [
                'variation' => $group_item_variation,
                'discount_multiplier' => $group_item_discount_multiplier,
                'quantity' => 1,
                'price' => $addon_price,
              ];
            }
          }
        }
      }

      // If the parent variation is included in the discounted,
      // set price data for the order processor.
      if ($parent_variation->hasField('include_parent') && $parent_variation->get('include_parent')->value) {
        $parent_vado_discount_price = $parent_variation->getPrice()->multiply($bundle_discount_multiplier);
        $order_item->setData('commerce_vado_discount_price', $parent_vado_discount_price);
      }

      // VADO OrderItemMatcher uses this ID to determine if synced order items
      // should combine in the cart or if a new bundle should be created.
      $vado_combo_id = $this->getVadoComboId($order_item, $addon_data);

      foreach ($addon_data as $data) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $child_variation */
        $child_variation = $data['variation'];
        $child_price = $data['price'];
        $child_price = $this->rounder->round($child_price);
        $quantity = $data['quantity'];

        // If parent is excluded we multiply the data quantity by the parent,
        // to account for parents that have more than one of the same child.
        if ($order_item->getData('commerce_vado_exclude_parent')) {
          $quantity = $order_item->getQuantity() * $data['quantity'];
        }

        $child_order_item = $order_item_storage->create([
          'title' => $child_variation->getOrderItemTitle(),
          'type' => $child_variation->getOrderItemTypeId(),
          'unit_price' => $child_price,
          'purchased_entity' => $child_variation->id(),
          'quantity' => $quantity,
        ]);

        // Only set child data if parent is not excluded.
        if (!$order_item->getData('commerce_vado_exclude_parent')) {
          // Let the vado order processor know the vado discount price.
          $child_order_item->setData('commerce_vado_discount_price', $data['price']);
          // Whether sync quantity is enabled when this order item was created.
          $child_order_item->setData('commerce_vado_synced_child_order_item', $sync_quantity);
          // A reference to the parent variations order item.
          $child_order_item->setData('commerce_vado_parent_order_item', $order_item->id());
          // Used in the order item matcher to as a unique ID per vado bundle.
          $child_order_item->setData('commerce_vado_combo_id', $vado_combo_id);
          // The bundle quantity so sync knows the quantity to calculate.
          $child_order_item->setData('commerce_vado_bundle_quantity', $data['quantity']);
        }

        $matching_order_item = $this->orderItemMatcher->match($child_order_item, $order->getItems());
        if ($matching_order_item) {
          $new_quantity = Calculator::add($matching_order_item->getQuantity(), $child_order_item->getQuantity());
          $matching_order_item->setQuantity($new_quantity);
          $matching_order_item->save();
        }
        else {
          $child_order_item->save();
          $child_order_items[] = $child_order_item->id();
          $order->addItem($child_order_item);
        }
      }

      // If the parent is excluded, delete the parent and move on.
      if ($order_item->getData('commerce_vado_exclude_parent')) {
        $order_item->delete();
        return;
      }

      $order_item->setData('commerce_vado_combo_id', $vado_combo_id);
      $matching_order_item = $this->orderItemMatcher->match($order_item, $order->getItems());
      if ($matching_order_item) {
        $new_quantity = Calculator::add($matching_order_item->getQuantity(), $order_item->getQuantity());
        $matching_order_item->setQuantity($new_quantity);
        $matching_order_item->save();
        $order_item->delete();
      }
      else {
        $order_item->setData('commerce_vado_child_order_items', $child_order_items);
        $order_item->save();
      }
    }
  }

  /**
   * Gets a unique string combo ID for VADO OrderItemMatcher.
   *
   * This ID is the combination of the parent variation ID,
   * and all of the addon variations ids that were selected it,
   * separated by a dash.
   *
   * Example:
   *  Parent Variation ID = 1
   *  Addon Variation IDs = 2, 4, 6, 7
   *
   * Output: 1-2-4-6-7
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item responsible for generating addons.
   * @param array $addon_data
   *   The variation addon data with the following keys:
   *     'variation' => ProductVariationInterface,
   *     'discount_multiplier' => string.
   *
   * @return string
   *   The vado combination ID.
   */
  protected function getVadoComboId(OrderItemInterface $order_item, array $addon_data) {
    $combo_id = $order_item->getPurchasedEntityId();
    $addon_variation_ids = array_map(function ($data) {
      return $data['variation']->id();
    }, $addon_data);
    $combo_id .= implode("-", $addon_variation_ids);

    return $combo_id;
  }

  /**
   * Set the quantity of the synced children in the cart to the parent quantity.
   */
  public function orderPreSaveEvent(OrderEvent $event) {
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $order = $event->getOrder();

    // The order total needs to be recalculated if order item quantity changes.
    $recalculate_total = FALSE;
    foreach ($order->getItems() as $order_item) {
      $quantity = $order_item->getQuantity();
      $child_order_item_ids = $order_item->getData('commerce_vado_child_order_items', []);
      $child_order_items = $order_item_storage->loadMultiple($child_order_item_ids);
      foreach ($child_order_items as $child_order_item) {
        // The base quantity of this item in the bundle.
        $bundle_quantity = $child_order_item->getData('commerce_vado_bundle_quantity');
        // Whether the quantity needs to be synced based on the bundle quantity.
        $needs_quantity_sync = ($child_order_item->getQuantity() / $bundle_quantity) !== $quantity;
        if ($child_order_item->getData('commerce_vado_synced_child_order_item') && $needs_quantity_sync) {
          $child_order_item->setQuantity($quantity * $bundle_quantity);
          $child_order_item->save();
          $recalculate_total = TRUE;
        }
      }
    }
    if ($recalculate_total) {
      $order->recalculateTotalPrice();
    }
  }

  /**
   * Delete synced children from the cart when the parent is deleted.
   */
  public function orderItemDeleteEvent(OrderItemEvent $event) {
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $order_item = $event->getOrderItem();

    $parent_variation = $order_item->getPurchasedEntity();
    if (!$parent_variation) {
      return;
    }

    $sync_quantity = FALSE;
    if ($parent_variation->hasField('sync_quantity') && !$parent_variation->get('sync_quantity')->isEmpty()) {
      $sync_quantity = $parent_variation->get('sync_quantity')->value;
    }
    if ($sync_quantity) {
      $child_order_item_ids = $order_item->getData('commerce_vado_child_order_items', []);
      if (!empty($child_order_item_ids)) {
        $child_order_items = $order_item_storage->loadMultiple($child_order_item_ids);
        $order_item_storage->delete($child_order_items);
      }
    }
  }

}
