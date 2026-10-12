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
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * A test tool covering the full input definition matrix.
 *
 * One input per (data type × configuration) combination that the schema
 * emission and coercion matrix tests exercise. Every input except
 * required_string is optional so each test case can set exactly the inputs
 * it is about. The tool echoes every executable value back through the
 * single 'received' output so tests can observe what doExecute() was given.
 */
#[Tool(
  id: 'schema_matrix_tool',
  label: new TranslatableMarkup('Schema Matrix Tool'),
  description: new TranslatableMarkup('Covers the input definition matrix for schema and coercion tests.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'required_string' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Required string'),
      description: new TranslatableMarkup('A required string input.'),
    ),
    'plain_string' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Plain string'),
      description: new TranslatableMarkup('An optional string input.'),
      required: FALSE,
    ),
    'length_string' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Length string'),
      description: new TranslatableMarkup('A length-constrained string input.'),
      required: FALSE,
      constraints: ['Length' => ['min' => 2, 'max' => 8]],
    ),
    'regex_string' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Regex string'),
      description: new TranslatableMarkup('A pattern-constrained string input.'),
      required: FALSE,
      constraints: ['Regex' => ['pattern' => '/^[a-z]{3}-\d{2}$/']],
    ),
    'email_type' => new InputDefinition(
      data_type: 'email',
      label: new TranslatableMarkup('Email type'),
      description: new TranslatableMarkup('An email data type input.'),
      required: FALSE,
    ),
    'uri_type' => new InputDefinition(
      data_type: 'uri',
      label: new TranslatableMarkup('URI type'),
      description: new TranslatableMarkup('A URI data type input.'),
      required: FALSE,
    ),
    'plain_integer' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Plain integer'),
      description: new TranslatableMarkup('An optional integer input.'),
      required: FALSE,
    ),
    'range_integer' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Range integer'),
      description: new TranslatableMarkup('A range-constrained integer input.'),
      required: FALSE,
      constraints: ['Range' => ['min' => 1, 'max' => 10]],
    ),
    'plain_float' => new InputDefinition(
      data_type: 'float',
      label: new TranslatableMarkup('Plain float'),
      description: new TranslatableMarkup('An optional float input.'),
      required: FALSE,
    ),
    'plain_decimal' => new InputDefinition(
      data_type: 'decimal',
      label: new TranslatableMarkup('Plain decimal'),
      description: new TranslatableMarkup('An optional decimal input.'),
      required: FALSE,
    ),
    'plain_boolean' => new InputDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup('Plain boolean'),
      description: new TranslatableMarkup('An optional boolean input.'),
      required: FALSE,
    ),
    'plain_timestamp' => new InputDefinition(
      data_type: 'timestamp',
      label: new TranslatableMarkup('Plain timestamp'),
      description: new TranslatableMarkup('An optional timestamp input.'),
      required: FALSE,
    ),
    'datetime_full' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Datetime full'),
      description: new TranslatableMarkup('A date and time input.'),
      required: FALSE,
      constraints: ['DateTime' => []],
    ),
    'datetime_date' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Datetime date'),
      description: new TranslatableMarkup('A date-only input.'),
      required: FALSE,
      constraints: ['DateOnly' => []],
    ),
    'datetime_bare' => new InputDefinition(
      data_type: 'datetime_iso8601',
      label: new TranslatableMarkup('Datetime bare'),
      description: new TranslatableMarkup('A datetime input without a format marker.'),
      required: FALSE,
    ),
    'enum_string' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Enum string'),
      description: new TranslatableMarkup('A choice-constrained string input.'),
      required: FALSE,
      constraints: ['Choice' => ['choices' => ['alpha', 'beta', 'gamma']]],
    ),
    'enum_integer' => new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Enum integer'),
      description: new TranslatableMarkup('A choice-constrained integer input.'),
      required: FALSE,
      constraints: ['AllowedValues' => ['choices' => [1, 2, 3]]],
    ),
    // The multi_* inputs and deep_variants are declared as the lists that
    // multiple: TRUE converts to (#3583049): each item is required and
    // carries no label or description, so the snapshots of the former
    // multiple inputs hold.
    'multi_string' => new ListInputDefinition(
      label: new TranslatableMarkup('Multi string'),
      description: new TranslatableMarkup('A multiple string input.'),
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'string',
        label: '',
        description: '',
      ),
    ),
    'integer_list' => new ListInputDefinition(
      label: new TranslatableMarkup('Integer list'),
      description: new TranslatableMarkup('A list of integers.'),
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'integer',
        label: new TranslatableMarkup('Integer item'),
        description: new TranslatableMarkup('An integer item.'),
      ),
    ),
    'multi_datetime' => new ListInputDefinition(
      label: new TranslatableMarkup('Multi datetime'),
      description: new TranslatableMarkup('A multiple date and time input.'),
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'datetime_iso8601',
        label: '',
        description: '',
        constraints: ['DateTime' => []],
      ),
    ),
    'free_map' => new MapInputDefinition(
      label: new TranslatableMarkup('Free map'),
      description: new TranslatableMarkup('A map without declared properties.'),
      required: FALSE,
    ),
    'typed_map' => new MapInputDefinition(
      label: new TranslatableMarkup('Typed map'),
      description: new TranslatableMarkup('A map with typed properties.'),
      required: FALSE,
      property_definitions: [
        'title' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Title'),
          description: new TranslatableMarkup('A required string property.'),
        ),
        'limit' => new InputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Limit'),
          description: new TranslatableMarkup('An integer property.'),
          required: FALSE,
        ),
        'verbose' => new InputDefinition(
          data_type: 'boolean',
          label: new TranslatableMarkup('Verbose'),
          description: new TranslatableMarkup('A boolean property.'),
          required: FALSE,
        ),
      ],
    ),
    'multi_map' => new ListInputDefinition(
      label: new TranslatableMarkup('Multi map'),
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
    // Count describes the list, so the list is declared explicitly via
    // ListInputDefinition; the item definition carries the item's shape.
    'bounded_maps' => new ListInputDefinition(
      label: new TranslatableMarkup('Bounded maps'),
      description: new TranslatableMarkup('A count-bounded list of maps.'),
      required: FALSE,
      constraints: ['Count' => ['max' => 1]],
      item_definition: new MapInputDefinition(
        label: new TranslatableMarkup('Bounded item'),
        description: new TranslatableMarkup('A map item with two properties.'),
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
    // Count on a map counts its properties.
    'bounded_map' => new MapInputDefinition(
      label: new TranslatableMarkup('Bounded map'),
      description: new TranslatableMarkup('A count-bounded map of optional properties.'),
      required: FALSE,
      constraints: ['Count' => ['min' => 1, 'max' => 2]],
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
        'note' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Note'),
          description: new TranslatableMarkup('A free text note.'),
          required: FALSE,
        ),
      ],
    ),
    'nested_map' => new MapInputDefinition(
      label: new TranslatableMarkup('Nested map'),
      description: new TranslatableMarkup('A map holding a map and a list.'),
      required: FALSE,
      property_definitions: [
        'inner' => new MapInputDefinition(
          label: new TranslatableMarkup('Inner map'),
          description: new TranslatableMarkup('A nested map property.'),
          required: FALSE,
          property_definitions: [
            'count' => new InputDefinition(
              data_type: 'integer',
              label: new TranslatableMarkup('Count'),
              description: new TranslatableMarkup('An integer inside the nested map.'),
              required: FALSE,
            ),
          ],
        ),
        'flags' => new ListInputDefinition(
          label: new TranslatableMarkup('Flags'),
          description: new TranslatableMarkup('A list of booleans inside the map.'),
          required: FALSE,
          item_definition: new InputDefinition(
            data_type: 'boolean',
            label: new TranslatableMarkup('Flag'),
            description: new TranslatableMarkup('A boolean item.'),
          ),
        ),
      ],
    ),
    'deep_variants' => new ListInputDefinition(
      label: new TranslatableMarkup('Deep variants'),
      description: new TranslatableMarkup('A multiple map whose items hold a list.'),
      required: FALSE,
      item_definition: new MapInputDefinition(
        label: '',
        description: '',
        property_definitions: [
          'name' => new InputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('Name'),
            description: new TranslatableMarkup('A string property.'),
            required: FALSE,
          ),
          'sizes' => new ListInputDefinition(
            label: new TranslatableMarkup('Sizes'),
            description: new TranslatableMarkup('A list of integers inside each item.'),
            required: FALSE,
            item_definition: new InputDefinition(
              data_type: 'integer',
              label: new TranslatableMarkup('Size'),
              description: new TranslatableMarkup('An integer size.'),
            ),
          ),
        ],
      ),
    ),
    'any_value' => new InputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Any value'),
      description: new TranslatableMarkup('An input without a declared type.'),
      required: FALSE,
    ),
    'default_string' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Default string'),
      description: new TranslatableMarkup('A string input with a default value.'),
      required: FALSE,
      default_value: 'fallback',
    ),
  ],
  output_definitions: [
    'received' => new OutputDefinition(
      data_type: 'any',
      label: new TranslatableMarkup('Received'),
      description: new TranslatableMarkup('Every executable value the tool received.'),
    ),
  ],
)]
final class SchemaMatrixTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(
      new TranslatableMarkup('Schema matrix executed.'),
      ['received' => $values],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
