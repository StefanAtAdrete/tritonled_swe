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
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool that reports the acting user ID.
 */
#[Tool(
  id: 'current_user_tool',
  label: new TranslatableMarkup('Current User Tool'),
  description: new TranslatableMarkup('A test tool that reports the ID of the user executing it.'),
  operation: ToolOperation::Read,
  output_definitions: [
    'uid' => new OutputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('User ID'),
      description: new TranslatableMarkup('The ID of the user executing the tool.'),
    ),
  ],
)]
final class CurrentUserTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Current user reported.'),
      ['uid' => (int) $this->currentUser->id()],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
