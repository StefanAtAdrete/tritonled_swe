<?php

namespace Drupal\commerce_vado;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The vado group widget manager.
 */
class VadoGroupWidgetManager extends DefaultPluginManager {

  /**
   * Constructs a new VadoGroupWidgetManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Commerce/VadoGroupWidget', $namespaces, $module_handler, 'Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget\VadoGroupWidgetInterface', 'Drupal\commerce_vado\Annotation\CommerceVadoGroupWidget');

    $this->alterInfo('commerce_vado_group_widget_info');
    $this->setCacheBackend($cache_backend, 'commerce_vado_group_widget_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The vado group widget %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
