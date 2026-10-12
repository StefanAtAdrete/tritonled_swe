<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\tool\Tool\ToolDefinition;

/**
 * Derives one tool per way of declaring a permission.
 */
final class DerivedPermissionToolDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof ToolDefinition);
    $declarations = [
      'single' => 'operate test tools',
      'any' => 'operate test tools+audit test tools',
      'spaced' => ' operate test tools , audit test tools ',
      'misspelled' => 'operate test tool',
    ];
    foreach ($declarations as $derivative_id => $permission) {
      $this->derivatives[$derivative_id] = (clone $base_plugin_definition)->setPermission($permission);
    }
    return $this->derivatives;
  }

}
