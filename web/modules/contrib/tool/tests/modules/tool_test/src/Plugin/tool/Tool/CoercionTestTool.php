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
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool for verifying input type coercion behavior.
 */
#[Tool(
  id: 'coercion_test_tool',
  label: new TranslatableMarkup('Coercion Test Tool'),
  description: new TranslatableMarkup('A test tool with various input types for coercion testing.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    // The tags and variants inputs are declared as the lists that
    // multiple: TRUE converts to (#3583049): their unlabeled items keep the
    // schema of the former multiple inputs.
    'tags' => new ListInputDefinition(
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('A multiple string input.'),
      item_definition: new InputDefinition(
        data_type: 'string',
        label: '',
        description: '',
      ),
    ),
    'enabled' => new InputDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup('Enabled'),
      description: new TranslatableMarkup('A boolean input.'),
    ),
    'numbers' => new ListInputDefinition(
      label: new TranslatableMarkup('Numbers'),
      description: new TranslatableMarkup('A list of integers.'),
      item_definition: new InputDefinition(
        data_type: 'integer',
        label: new TranslatableMarkup('Number'),
        description: new TranslatableMarkup('An integer item.'),
      ),
    ),
    'settings' => new MapInputDefinition(
      label: new TranslatableMarkup('Settings'),
      description: new TranslatableMarkup('A map with a boolean property.'),
      required: FALSE,
      property_definitions: [
        'verbose' => new InputDefinition(
          data_type: 'boolean',
          label: new TranslatableMarkup('Verbose'),
          description: new TranslatableMarkup('Whether verbose output is enabled.'),
          required: FALSE,
        ),
        'limit' => new InputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Limit'),
          description: new TranslatableMarkup('A numeric limit.'),
          required: FALSE,
        ),
      ],
    ),
    'variants' => new ListInputDefinition(
      label: new TranslatableMarkup('Variants'),
      description: new TranslatableMarkup('A multiple map input.'),
      required: FALSE,
      item_definition: new MapInputDefinition(
        label: '',
        description: '',
        property_definitions: [
          'sku' => new InputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('SKU'),
            description: new TranslatableMarkup('A stock keeping unit.'),
            required: FALSE,
          ),
          'quantity' => new InputDefinition(
            data_type: 'integer',
            label: new TranslatableMarkup('Quantity'),
            description: new TranslatableMarkup('A quantity.'),
            required: FALSE,
          ),
        ],
      ),
    ),
    'category' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Category'),
      description: new TranslatableMarkup('An enum-constrained string input.'),
      required: FALSE,
      constraints: ['Choice' => ['choices' => ['article', 'news_item', 'page']]],
    ),
    'published_at' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Published at'),
      description: new TranslatableMarkup('A datetime input.'),
      required: FALSE,
    ),
    'starts_on' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Starts on'),
      description: new TranslatableMarkup('A date-only input.'),
      required: FALSE,
      constraints: ['DateOnly' => []],
    ),
    'occurs_at' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Occurs at'),
      description: new TranslatableMarkup('A date and time input.'),
      required: FALSE,
      constraints: ['DateTime' => []],
    ),
    'happened_at' => new InputDefinition(
      data_type: 'timestamp',
      label: new TranslatableMarkup('Happened at'),
      description: new TranslatableMarkup('A timestamp input.'),
      required: FALSE,
    ),
    'threshold' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Threshold'),
      description: new TranslatableMarkup('An optional integer input.'),
      required: FALSE,
    ),
    'price' => new InputDefinition(
      data_type: 'decimal',
      label: new TranslatableMarkup('Price'),
      description: new TranslatableMarkup('An optional decimal input.'),
      required: FALSE,
    ),
  ],
  output_definitions: [
    // The tags and numbers outputs are declared as the lists that
    // multiple: TRUE converts to (#3583049), like the inputs above.
    'tags' => new ListOutputDefinition(
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('The tags that were received.'),
      item_definition: new OutputDefinition(
        data_type: 'string',
        label: '',
        description: '',
      ),
    ),
    'enabled' => new OutputDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup('Enabled'),
      description: new TranslatableMarkup('The boolean that was received.'),
    ),
    'numbers' => new ListOutputDefinition(
      label: new TranslatableMarkup('Numbers'),
      description: new TranslatableMarkup('The numbers that were received.'),
      item_definition: new OutputDefinition(
        data_type: 'integer',
        label: '',
        description: '',
      ),
    ),
    'settings' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Settings'),
      required: FALSE,
      description: new TranslatableMarkup('The settings map that was received.'),
    ),
    'category' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Category'),
      required: FALSE,
      description: new TranslatableMarkup('The category that was received.'),
    ),
    'variants' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Variants'),
      required: FALSE,
      description: new TranslatableMarkup('The variants that were received.'),
    ),
    'published_at' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Published at'),
      required: FALSE,
      description: new TranslatableMarkup('The datetime that was received.'),
    ),
    'starts_on' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Starts on'),
      required: FALSE,
      description: new TranslatableMarkup('The date that was received.'),
    ),
    'occurs_at' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Occurs at'),
      required: FALSE,
      description: new TranslatableMarkup('The date and time that was received.'),
    ),
    'happened_at' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Happened at'),
      required: FALSE,
      description: new TranslatableMarkup('The timestamp that was received.'),
    ),
    'threshold' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Threshold'),
      required: FALSE,
      description: new TranslatableMarkup('The threshold that was received.'),
    ),
    'price' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Price'),
      required: FALSE,
      description: new TranslatableMarkup('The price that was received.'),
    ),
  ],
)]
final class CoercionTestTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Coercion test executed.'),
      [
        'tags' => $values['tags'],
        'enabled' => $values['enabled'],
        'numbers' => $values['numbers'],
        'settings' => $values['settings'],
        'variants' => $values['variants'] ?? NULL,
        'category' => $values['category'] ?? NULL,
        'published_at' => $values['published_at'] ?? NULL,
        'starts_on' => $values['starts_on'] ?? NULL,
        'occurs_at' => $values['occurs_at'] ?? NULL,
        'happened_at' => $values['happened_at'] ?? NULL,
        'threshold' => $values['threshold'] ?? NULL,
        'price' => $values['price'] ?? NULL,
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
