<?php

namespace Drupal\commerce_vado;

use Drupal\commerce\Context;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies vado discounts to orders during the order refresh process.
 */
class VadoOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

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
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new PromotionOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Configuration Factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RounderInterface $rounder, RequestStack $request_stack, ChainPriceResolverInterface $chain_price_resolver, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rounder = $rounder;
    $this->request = $request_stack->getCurrentRequest();
    $this->chainPriceResolver = $chain_price_resolver;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $order_items = $order->getItems();

    // Check if the order processor is being run by the order price calculator.
    if ($order->getData('provider') === 'order_price_calculator' && count($order_items) === 1) {
      $order_item = reset($order_items);
      $this->calculateBundlePrice($order_item);
      return;
    }

    foreach ($order_items as $order_item) {
      /** @var \Drupal\commerce_price\Price $vado_discounted_amount */
      if ($vado_discounted_price = $order_item->getData('commerce_vado_discount_price')) {
        $vado_discounted_amount = $order_item->getUnitPrice()->subtract($vado_discounted_price);

        // If there is no discount, move on to the next item.
        if ($vado_discounted_amount->isZero()) {
          continue;
        }

        $vado_discounted_price = $this->rounder->round($vado_discounted_price);
        $order_item->setUnitPrice($vado_discounted_price);
        $order_item->addAdjustment(new Adjustment([
          'type' => 'vado_discount',
          'label' => $this->t('Vado discount'),
          'amount' => $vado_discounted_amount->multiply('-1'),
          'included' => TRUE,
        ]));
      }
    }
  }

  /**
   * Calculates the bundle price for the calculated price formatter.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  protected function calculateBundlePrice(OrderItemInterface $order_item) {
    $parent_variation = $order_item->getPurchasedEntity();
    if (!$parent_variation) {
      return;
    }

    $order = $order_item->getOrder();
    $price = $order_item->getUnitPrice();

    // If the parent variation is to be excluded from the order,
    // it won't get added to the cart. Therefore we make sure
    // to zero out it's price on the add to cart form.
    $exclude_parent = FALSE;
    if ($parent_variation->hasField('exclude_parent') && !$parent_variation->get('exclude_parent')->isEmpty()) {
      $exclude_parent = $parent_variation->get('exclude_parent')->value;
      if ($exclude_parent) {
        $price = $price->multiply(0);
      }
    }

    // Determine the bundle discount provided by the parent variation.
    // We ignore this discount if the parent is excluded.
    $bundle_discount_integer = 0;
    if (!$exclude_parent && $parent_variation->hasField('bundle_discount') && !$parent_variation->get('bundle_discount')->isEmpty()) {
      $bundle_discount_integer = $parent_variation->get('bundle_discount')->value;
    }
    $bundle_discount_multiplier = (100 - $bundle_discount_integer) / 100;

    // If the parent variation is included in the bundle discount, apply it.
    if ($parent_variation->hasField('include_parent') && $parent_variation->get('include_parent')->value) {
      $price = $price->multiply($bundle_discount_multiplier);
    }

    // Order processors don't have access to the cart data. Selected group items
    // must be determined by getting the information directly from POST data.
    $selected_group_item_ids = $this->request->request->all()['commerce_vado_group'] ?? [];

    // Whether AJAX was just triggered by a vado group selection.
    $triggering_element_name = $this->request->request->all()['_triggering_element_name'] ?? '';
    $vado_ajax_detected = strpos($triggering_element_name, 'commerce_vado_group[addon_group_') !== FALSE;

    // On first page load, no group items are selected in request parameters.
    // Grab the default values that are populated in the group widget.
    // If a group selection just triggered AJAX and no selections are present,
    // do not load the defaults. The user has opted to not select any addons.
    if (!$vado_ajax_detected && empty($selected_group_item_ids) && $parent_variation->hasField('variation_groups')) {
      $groups = $parent_variation->get('variation_groups')->referencedEntities();
      /** @var \Drupal\commerce_vado\Entity\VadoGroupInterface $group */
      foreach ($groups as $group) {
        // Getting the default value from the widget is more reliable.
        // In cases like static list, all items are added by default.
        $selected_group_item_ids[] = $group->getGroupWidget()->getDefaultValue();
      }
    }

    // Flattens the array of group item ids.
    if (!empty($selected_group_item_ids)) {
      $selected_group_item_ids = VadoHelper::processGroupSelections($selected_group_item_ids);
    }

    // Get the setting for how to handle unpublished variations.
    $allow_unpublished = $this->configFactory->get('commerce_vado.settings')->get('allow_unpublished_variations');

    // Calculate the price from the variation addons, if present.
    if ($parent_variation->hasField('child_variations') && !$parent_variation->get('child_variations')->isEmpty()) {
      $context = new Context($order->getCustomer(), $order->getStore(), NULL, [
        'commerce_vado_parent_variation' => $parent_variation,
      ]);

      /** @var \Drupal\commerce\PurchasableEntityInterface $addon_variation */
      foreach ($parent_variation->get('child_variations')->referencedEntities() as $addon_variation) {
        // If the add-on isn't allowed to be added when unpublished, skip it.
        if (!$allow_unpublished && !$addon_variation->isPublished()) {
          continue;
        }
        $addon_price = $this->chainPriceResolver->resolve($addon_variation, 1, $context);
        $addon_price = $addon_price->multiply($bundle_discount_multiplier);
        $addon_price = $this->rounder->round($addon_price);
        $price = $price->add($addon_price);
      }
    }

    // Calculate price from selected group items.
    if (!empty($selected_group_item_ids)) {
      $vado_group_item_storage = $this->entityTypeManager->getStorage('commerce_vado_group_item');
      $group_items = $vado_group_item_storage->loadMultiple($selected_group_item_ids);
      if (!empty($group_items)) {
        /** @var \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item */
        foreach ($group_items as $group_item) {
          // If the group item doesn't have a variation entity,
          // or if the settings don't allow unpublished variations, continue.
          if (!$group_item->getVariation() || !$allow_unpublished && !$group_item->getVariation()->isPublished()) {
            continue;
          }
          // Use the group_discount on the group item if available.
          // If there is no group_discount, use the bundle_discount.
          $group = $group_item->getGroup();
          $group_discount_multiplier = $bundle_discount_multiplier;
          if ($group->hasGroupDiscount()) {
            $group_discount_multiplier = $group->getGroupDiscountMultiplier();
          }
          // Use the group_item_discount on the group item if available.
          // If there is no group_item_discount, use the group_discount.
          $group_item_discount_multiplier = $group_discount_multiplier;
          if ($group_item->hasGroupItemDiscount()) {
            $group_item_discount_multiplier = $group_item->getGroupItemDiscountMultiplier();
          }
          $context = new Context($order->getCustomer(), $order->getStore(), NULL, [
            'commerce_vado_parent_variation' => $parent_variation,
            'commerce_vado_group_item' => $group_item,
          ]);
          // Get the group item variation and apply the discount.
          $variation = $group_item->getVariation();
          $variation_price = $this->chainPriceResolver->resolve($variation, 1, $context);
          $variation_price = $variation_price->multiply($group_item_discount_multiplier);
          $variation_price = $this->rounder->round($variation_price);
          $price = $price->add($variation_price);
        }
      }
    }

    $parent_vado_discount_amount = $order_item->getUnitPrice()->subtract($price);
    $order_item->setUnitPrice($price);
    $order_item->addAdjustment(new Adjustment([
      'type' => 'vado_discount',
      'label' => $this->t('Vado discount'),
      'amount' => $parent_vado_discount_amount->multiply('-1'),
      'included' => TRUE,
    ]));
  }

}
