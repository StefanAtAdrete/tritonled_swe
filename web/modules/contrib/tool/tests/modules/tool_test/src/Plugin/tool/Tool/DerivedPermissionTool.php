<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool_test\Plugin\Derivative\DerivedPermissionToolDeriver;

/**
 * A test tool whose derivatives rely on their declared permission for access.
 */
#[Tool(
  id: 'derived_permission_tool',
  label: new TranslatableMarkup('Derived Permission Tool'),
  description: new TranslatableMarkup('A test tool whose derivatives declare different permissions.'),
  operation: ToolOperation::Read,
  deriver: DerivedPermissionToolDeriver::class,
)]
final class DerivedPermissionTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(new TranslatableMarkup('Done.'));
  }

}
