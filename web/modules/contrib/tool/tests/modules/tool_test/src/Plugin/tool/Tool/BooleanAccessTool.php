<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;

/**
 * A test tool whose access check returns a bare bool.
 */
#[Tool(
  id: 'boolean_access_tool',
  label: new TranslatableMarkup('Boolean Access Tool'),
  description: new TranslatableMarkup('A test tool whose access check returns a bool.'),
  operation: ToolOperation::Read,
  permission: 'operate test tools',
)]
final class BooleanAccessTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(new TranslatableMarkup('Done.'));
  }

  /**
   * {@inheritdoc}
   *
   * Ignores $return_as_object and returns a bare bool, which exercises the
   * bool normalization in ToolBase::access().
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $account->hasPermission('audit test tools');
  }

}
