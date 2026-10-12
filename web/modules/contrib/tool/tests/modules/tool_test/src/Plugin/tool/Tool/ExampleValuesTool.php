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
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool declaring input examples in the attribute.
 *
 * Covered by InputExamplesEmissionTest, which asserts the examples reach the
 * emitted JSON Schema through real plugin discovery and validate against it.
 */
#[Tool(
  id: 'example_values_tool',
  label: new TranslatableMarkup('Example Values Tool'),
  description: new TranslatableMarkup('A test tool with example values on its inputs.'),
  operation: ToolOperation::Read,
  input_definitions: [
    'field_name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Field name'),
      description: new TranslatableMarkup('The machine name of a field.'),
      examples: ['field_tags', 'field_summary'],
    ),
    'weight' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Weight'),
      description: new TranslatableMarkup('A weight between 0 and 100.'),
      required: FALSE,
      constraints: ['Range' => ['min' => 0, 'max' => 100]],
      examples: [0, 50, 100],
    ),
    // Examples describe a single value, so a list declares them on its
    // item. The item carries no label or description, as the item of the
    // former multiple input did (#3583049).
    'aliases' => new ListInputDefinition(
      label: new TranslatableMarkup('Aliases'),
      description: new TranslatableMarkup('Alias values.'),
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'string',
        label: '',
        description: '',
        examples: ['alpha', 'beta'],
      ),
    ),
  ],
  output_definitions: [
    'received' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Received'),
      description: new TranslatableMarkup('The field name that was received.'),
    ),
  ],
)]
final class ExampleValuesTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Executed with field name: @field_name', ['@field_name' => $values['field_name']]),
      ['received' => $values['field_name']]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
