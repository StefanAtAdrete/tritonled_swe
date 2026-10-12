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
 * A test tool whose result does not line up with its output definitions.
 *
 * Its result contains an undeclared context value, and it declares an
 * optional output that it never sets. Used to verify that both mismatches
 * are tolerated: undeclared values pass through untransformed, and skipped
 * optional outputs read as NULL.
 */
#[Tool(
  id: 'extra_output_tool',
  label: new TranslatableMarkup('Extra Output Tool'),
  description: new TranslatableMarkup('A test tool that returns more context values than it declares.'),
  operation: ToolOperation::Read,
  output_definitions: [
    'declared' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Declared'),
      description: new TranslatableMarkup('A declared output.'),
    ),
    'optional_note' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Optional note'),
      required: FALSE,
      description: new TranslatableMarkup('An optional output the tool never sets.'),
    ),
  ],
)]
final class ExtraOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Extra output test executed.'),
      [
        'declared' => 'declared value',
        'extra_debug' => 'undeclared value',
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
