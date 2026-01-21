<?php

namespace Drupal\commerce_vado\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * The route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.commerce_vado_groups.page_1')) {
      // Upcast the %commerce_product argument to the product entity
      // so the route loads properly.
      $route->setOption('parameters', ['commerce_product' => ['type' => 'entity:commerce_product']]);
      // Show the page in the admin theme.
      $route->setOption('_admin_route', TRUE);
      // Custom access check to hide the tab if the product
      // has no variations referencing groups.
      $route->setRequirement('_custom_access', '\Drupal\commerce_vado\Access\ProductVadoGroupAccessCheck::access');
    }
  }

}
