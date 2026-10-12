<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;

/**
 * A test tool whose access is gated on a permission.
 */
#[Tool(
  id: 'permission_gated_tool',
  label: new TranslatableMarkup('Permission Gated Tool'),
  description: new TranslatableMarkup('A test tool that requires the "administer users" permission.'),
  operation: ToolOperation::Read,
)]
final class PermissionGatedTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(new TranslatableMarkup('Permission granted.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $access = AccessResult::allowedIfHasPermission($account, 'administer users');
    return $return_as_object ? $access : $access->isAllowed();
  }

}
