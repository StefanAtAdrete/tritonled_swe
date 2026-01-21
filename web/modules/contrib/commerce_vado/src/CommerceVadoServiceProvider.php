<?php

namespace Drupal\commerce_vado;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * The commerce_vado service provider.
 */
class CommerceVadoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('commerce_cart.order_item_matcher');
    if ($definition) {
      $definition->setClass(OrderItemMatcher::class);
    }
  }

}
