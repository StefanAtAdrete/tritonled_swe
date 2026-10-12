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
 * A test tool that always denies access.
 *
 * Used to exercise genuine access denials, as opposed to input validation
 * failures that also surface through access() failing closed.
 */
#[Tool(
  id: 'access_denied_tool',
  label: new TranslatableMarkup('Access Denied Tool'),
  description: new TranslatableMarkup('A tool whose access check always denies.'),
  operation: ToolOperation::Transform,
)]
final class AccessDeniedTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('This should never execute.'),
      [],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $forbidden = AccessResult::forbidden('Denied by design.');
    return $return_as_object ? $forbidden : FALSE;
  }

}
