<?php

namespace Drupal\commerce_vado\EventSubscriber;

use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Registers the commerce_vado plugin types as referenceable.
 */
class ReferenceablePluginTypesSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce.referenceable_plugin_types' => 'onPluginTypes',
    ];
  }

  /**
   * Registers the 'commerce_vado_group_widget' plugin type as referenceable.
   *
   * @param \Drupal\commerce\Event\ReferenceablePluginTypesEvent $event
   *   The event.
   */
  public function onPluginTypes(ReferenceablePluginTypesEvent $event) {
    $plugin_types = $event->getPluginTypes();
    $plugin_types['commerce_vado_group_widget'] = t('Vado Group widget');
    $event->setPluginTypes($plugin_types);
  }

}
