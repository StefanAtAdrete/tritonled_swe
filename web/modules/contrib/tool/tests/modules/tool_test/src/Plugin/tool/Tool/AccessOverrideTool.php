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
 * A test tool whose only access rule is an access() override.
 *
 * It declares no permission and does not override checkAccess(): the
 * access() override alone satisfies the discovery check, which accepts
 * either override as an access rule.
 */
#[Tool(
  id: 'access_override_tool',
  label: new TranslatableMarkup('Access Override Tool'),
  description: new TranslatableMarkup('A test tool that replaces the whole access gate.'),
  operation: ToolOperation::Read,
)]
final class AccessOverrideTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  public function access(?AccountInterface $account = NULL, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(new TranslatableMarkup('Done.'));
  }

}
