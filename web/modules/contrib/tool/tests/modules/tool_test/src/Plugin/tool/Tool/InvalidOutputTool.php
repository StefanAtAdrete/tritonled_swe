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
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool whose result holds a value its output definition cannot store.
 *
 * ToolBase::execute() turns anything doExecute() throws into a failed result,
 * but it stores the result's outputs outside that guard, so a scalar returned
 * for a map output makes execute() itself throw. Used to exercise the one
 * execution failure ToolBase does not absorb.
 */
#[Tool(
  id: 'invalid_output_tool',
  label: new TranslatableMarkup('Invalid Output Tool'),
  description: new TranslatableMarkup('A test tool that returns a scalar for its map output.'),
  operation: ToolOperation::Transform,
  output_definitions: [
    'settings' => new MapOutputDefinition(
      label: new TranslatableMarkup('Settings'),
      description: new TranslatableMarkup('A map output.'),
      property_definitions: [
        'color' => new OutputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Color'),
          description: new TranslatableMarkup('A color setting.'),
        ),
      ],
    ),
  ],
)]
final class InvalidOutputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Returned a scalar for a map output.'),
      ['settings' => 'not a map'],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
