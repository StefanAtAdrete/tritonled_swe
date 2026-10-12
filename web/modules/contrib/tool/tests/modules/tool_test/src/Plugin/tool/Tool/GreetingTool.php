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
 * A test tool with inputs and outputs, used for testing.
 */
#[Tool(
  id: 'greeting_tool',
  label: new TranslatableMarkup('Greeting Tool'),
  description: new TranslatableMarkup('A test tool that generates a greeting message.'),
  operation: ToolOperation::Transform,
  destructive: TRUE,
  input_definitions: [
    'name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('The name to greet.'),
    ),
    'greeting' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Greeting'),
      description: new TranslatableMarkup('The greeting to use.'),
      required: FALSE,
    ),
  ],
  output_definitions: [
    'message' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Message'),
      description: new TranslatableMarkup('The generated greeting message.'),
    ),
  ],
)]
final class GreetingTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    $name = $values['name'];
    $greeting = $values['greeting'] ?? 'Hello';
    $message = "$greeting, $name!";

    return ExecutableResult::success(
      new TranslatableMarkup('Greeting generated.'),
      ['message' => $message],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
