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
 * A test tool whose curated failure message contains quotes.
 *
 * The invalid-input wrap escapes the message into a TranslatableMarkup
 * placeholder, so a text invoker that prints it unprocessed shows
 * &quot; entities (#3583042).
 */
#[Tool(
  id: 'quoted_failure_tool',
  label: new TranslatableMarkup('Quoted Failure Tool'),
  description: new TranslatableMarkup('A test tool that fails with quotes in its message.'),
  operation: ToolOperation::Read,
)]
final class QuotedFailureTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    throw new \InvalidArgumentException('The "subject" value is not usable.');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
