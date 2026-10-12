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
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * Test tool with a locked input alongside a normal one.
 *
 * Locked inputs cannot be changed by callers and always resolve to their
 * default value during execution.
 */
#[Tool(
  id: 'locked_input_tool',
  label: new TranslatableMarkup('Locked Input Tool'),
  description: new TranslatableMarkup('A test tool with a locked input.'),
  operation: ToolOperation::Transform,
  icon: 'tool_test:wrench',
  input_definitions: [
    'api_mode' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('API mode'),
      description: new TranslatableMarkup('A locked string input.'),
      required: FALSE,
      default_value: 'locked-default',
      locked: TRUE,
    ),
    'name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('A normal string input.'),
      required: FALSE,
    ),
  ],
  output_definitions: [
    'api_mode' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('API mode'),
      required: FALSE,
      description: new TranslatableMarkup('The api_mode value that was received.'),
    ),
    'name' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Name'),
      required: FALSE,
      description: new TranslatableMarkup('The name value that was received.'),
    ),
  ],
)]
final class LockedInputTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Locked input tool executed.'),
      [
        'api_mode' => $values['api_mode'] ?? NULL,
        'name' => $values['name'] ?? NULL,
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
