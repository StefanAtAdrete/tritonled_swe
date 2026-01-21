<?php

namespace Drupal\commerce_vado\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for the vado_groups view.
 *
 * Takes the product variation type ID from the product type, since a product
 * is always present in variation routes.
 */
class ProductVadoGroupAccessCheck implements AccessInterface {

  /**
   * Checks access to the vado groups view for products.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $route_match->getParameter('commerce_product');
    if (!$product) {
      return AccessResult::forbidden();
    }

    $has_groups = \Drupal::entityQuery('commerce_product_variation')
      ->accessCheck(FALSE)
      ->condition('product_id', $product->id())
      ->exists('variation_groups')
      ->count()
      ->execute();

    if ($has_groups) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
