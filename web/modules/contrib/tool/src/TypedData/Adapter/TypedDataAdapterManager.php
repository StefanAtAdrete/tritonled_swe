<?php

declare(strict_types=1);

namespace Drupal\tool\TypedData\Adapter;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\tool\Attribute\TypedDataAdapter;

/**
 * TypedDataAdapter plugin manager.
 */
final class TypedDataAdapterManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/tool/TypedData/Adapter', $namespaces, $module_handler, TypedDataAdapterInterface::class, TypedDataAdapter::class);
    $this->alterInfo('tool_typed_data_adapter_info');
    $this->setCacheBackend($cache_backend, 'tool_typed_data_adapter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Legacy unprefixed alter hook, kept for one beta cycle so existing
    // adapter implementers keep working. Fired before the prefixed hook so
    // current implementations win on conflict.
    if ($this->moduleHandler->hasImplementations('data_type_adapter_info_alter')) {
      @trigger_error('hook_data_type_adapter_info_alter() is deprecated in tool:1.0.0-beta4 and is removed from tool:1.0.0-rc1. Implement hook_tool_typed_data_adapter_info_alter() instead. See https://www.drupal.org/project/tool/issues/3582963', E_USER_DEPRECATED);
      $this->moduleHandler->alter('data_type_adapter_info', $definitions);
    }
    parent::alterDefinitions($definitions);
  }

  /**
   * Gets the appropriate adapter definition for a given data definition.
   */
  public function getDefinitionFromDataDefinition(DataDefinitionInterface $data_definition): ?array {
    $definitions = $this->getDefinitions();
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
    foreach ($definitions as $definition) {
      if ($definition['class']::isApplicable($data_definition)) {
        return $definition;
      }
    }
    return $this->getDefinition('undefined');
  }

}
