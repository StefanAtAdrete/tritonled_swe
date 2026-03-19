<?php

namespace Drupal\tritonled_configurator\Controller;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles adding configurator items to the Commerce cart.
 *
 * Receives a JSON POST from configurator.js with:
 * - variationId: int — the CONFIGURATOR-{id} dummy variation
 * - sku: string — the generated SKU (e.g. M-A0C8-J19N1)
 * - selections: object — the step selections as JSON
 */
class ConfiguratorCartController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected CartManagerInterface $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected CartProviderInterface $cartProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    CartManagerInterface $cart_manager,
    CartProviderInterface $cart_provider,
  ) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
    );
  }

  /**
   * Adds a configured product to the cart.
   */
  public function addToCart(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (!$data || empty($data['variationId']) || empty($data['sku'])) {
      return new JsonResponse([
        'error' => 'Missing required fields: variationId, sku.',
      ], 400);
    }

    $variation_id = (int) $data['variationId'];
    $sku = (string) $data['sku'];
    $selections = $data['selections'] ?? [];

    // Load the dummy variation — entityTypeManager comes from ControllerBase.
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface|null $variation */
    $variation = $this->entityTypeManager()
      ->getStorage('commerce_product_variation')
      ->load($variation_id);

    if (!$variation instanceof ProductVariationInterface) {
      return new JsonResponse([
        'error' => 'Variation not found: ' . $variation_id,
      ], 404);
    }

    $store = $variation->getProduct()->getStores()[0] ?? NULL;
    if (!$store) {
      return new JsonResponse(['error' => 'No store found for product.'], 500);
    }

    $cart = $this->cartProvider->getCart('default', $store)
      ?? $this->cartProvider->createCart('default', $store);

    // Disable the cart redirect by suppressing the response event.
    // addEntity() triggers CartEntityAddEvent which normally causes a redirect.
    // We create the order item manually to avoid this.
    $order_item_storage = $this->entityTypeManager()->getStorage('commerce_order_item');

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $order_item_storage->createFromPurchasableEntity($variation);
    $order_item->setQuantity(1);
    $order_item->save();

    $cart->addItem($order_item);
    $cart->save();

    if (!$order_item instanceof OrderItemInterface) {
      return new JsonResponse(['error' => 'Could not add item to cart.'], 500);
    }

    if ($order_item->hasField('field_configurator_sku')) {
      $order_item->set('field_configurator_sku', $sku);
    }
    if ($order_item->hasField('field_configurator_data')) {
      $order_item->set('field_configurator_data', json_encode($selections));
    }
    $order_item->save();

    return new JsonResponse([
      'success' => TRUE,
      'sku' => $sku,
      'order_item_id' => $order_item->id(),
      'cart_id' => $cart->id(),
    ], 200);
  }

}
