<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Matrix;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Snapshot tests for every input shape on the schema matrix tool.
 *
 * One assertion per (data type × configuration) combination, pinning the
 * exact JSON Schema the tool module advertises to invokers. Invokers such as
 * the MCP bridge validate arguments against this schema strictly before the
 * tool ever sees them, so any drift here is a wire-contract change.
 *
 * Scalar leaves differ per core: 11.1+ cores emit oneOf branches for
 * nullable scalars from core's schema-aware normalizers, while older cores
 * go through LegacyTypedDataNormalizer and emit type arrays. Each case pins
 * both shapes.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class InputSchemaMatrixTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'tool_test',
  ];

  /**
   * The full normalized input schema of schema_matrix_tool.
   *
   * @var array
   */
  protected array $schema;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $tool = $this->container->get('plugin.manager.tool')->createInstance('schema_matrix_tool');
    $this->schema = $this->container->get('tool.definition_serializer')->normalizeInputSchema($tool);
  }

  /**
   * Whether core's schema-aware normalizers (11.1+) handle scalars.
   */
  protected static function isCoreSchemaPath(): bool {
    return version_compare(\Drupal::VERSION, '11.1', '>=');
  }

  /**
   * Builds the nullable scalar schema for the active core path.
   *
   * @param string $type
   *   The JSON Schema type of the non-null branch.
   * @param array $branch
   *   Keywords emitted inside the non-null branch (e.g. format) on the core
   *   path; the legacy path merges them beside the type.
   *
   * @return array
   *   The schema fragment for the active path.
   */
  protected static function nullableScalar(string $type, array $branch = []): array {
    if (static::isCoreSchemaPath()) {
      return ['oneOf' => [['type' => $type] + $branch, ['type' => 'null']]];
    }
    return ['type' => [$type, 'null']] + $branch;
  }

  /**
   * Provides the name of every input on the matrix tool.
   *
   * Expectations live in expectedSchema(): closures cannot cross the
   * process-isolation boundary that RunTestsInSeparateProcesses imposes on
   * provider data, and the core-path check needs a booted environment.
   *
   * @return \Generator
   *   Cases of [input name].
   */
  public static function schemaProvider(): \Generator {
    yield 'required string has no null branch' => ['required_string'];
    yield 'optional string is nullable' => ['plain_string'];
    yield 'length constraint emits bounds' => ['length_string'];
    yield 'regex constraint emits ECMA pattern' => ['regex_string'];
    yield 'email data type emits email format' => ['email_type'];
    yield 'uri data type emits uri format' => ['uri_type'];
    yield 'optional integer is nullable' => ['plain_integer'];
    yield 'range constraint emits numeric bounds' => ['range_integer'];
    yield 'float emits number' => ['plain_float'];
    yield 'decimal emits patterned string on both paths' => ['plain_decimal'];
    yield 'boolean is nullable boolean' => ['plain_boolean'];
    yield 'timestamp documents its wire format per path' => ['plain_timestamp'];
    yield 'DateTime marker emits date-time format' => ['datetime_full'];
    yield 'DateOnly marker emits date format' => ['datetime_date'];
    yield 'unmarked datetime format differs per path' => ['datetime_bare'];
    yield 'string choices emit enum' => ['enum_string'];
    yield 'integer choices emit enum' => ['enum_integer'];
    yield 'multiple scalar wraps items in array' => ['multi_string'];
    yield 'list definition emits required item schema' => ['integer_list'];
    yield 'multiple datetime carries the marker into items' => ['multi_datetime'];
    yield 'map without properties stays an open object' => ['free_map'];
    yield 'typed map lists properties and required names' => ['typed_map'];
    yield 'multiple map emits array of objects' => ['multi_map'];
    yield 'count bound on a list definition emits maxItems' => ['bounded_maps'];
    yield 'count bound on a map definition emits property bounds' => ['bounded_map'];
    yield 'nested map nests object and array schemas' => ['nested_map'];
    yield 'multiple map items may nest lists' => ['deep_variants'];
    yield 'any emits an untyped schema on the core path' => ['any_value'];
    yield 'default value is advertised' => ['default_string'];
  }

  /**
   * Returns the expected schema for a matrix input on the active core path.
   *
   * @param string $name
   *   The input name on schema_matrix_tool.
   *
   * @return array
   *   The expected schema.
   */
  protected function expectedSchema(string $name): array {
    return match ($name) {
      'required_string' => [
        'type' => 'string',
        'description' => 'Required string: A required string input.',
        'title' => 'Required string',
      ],
      'plain_string' => static::nullableScalar('string') + [
        'description' => 'Plain string: An optional string input.',
        'title' => 'Plain string',
      ],
      'length_string' => static::nullableScalar('string') + [
        'minLength' => 2,
        'maxLength' => 8,
        'description' => 'Length string: A length-constrained string input.',
        'title' => 'Length string',
      ],
      'regex_string' => static::nullableScalar('string') + [
        'pattern' => '^[a-z]{3}-\d{2}$',
        'description' => 'Regex string: A pattern-constrained string input.',
        'title' => 'Regex string',
      ],
      'email_type' => static::nullableScalar('string', ['format' => 'email']) + [
        'description' => 'Email type: An email data type input.',
        'title' => 'Email type',
      ],
      'uri_type' => static::nullableScalar('string', ['format' => 'uri']) + [
        'description' => 'URI type: A URI data type input.',
        'title' => 'URI type',
      ],
      'plain_integer' => static::nullableScalar('integer') + [
        'description' => 'Plain integer: An optional integer input.',
        'title' => 'Plain integer',
      ],
      'range_integer' => static::nullableScalar('integer') + [
        'minimum' => 1,
        'maximum' => 10,
        'description' => 'Range integer: A range-constrained integer input.',
        'title' => 'Range integer',
      ],
      'plain_float' => static::nullableScalar('number') + [
        'description' => 'Plain float: An optional float input.',
        'title' => 'Plain float',
      ],
      'plain_decimal' => [
        'type' => ['string', 'null'],
        'pattern' => '^-?\d+(\.\d+)?$',
        'description' => 'Plain decimal: An optional decimal input. A decimal number formatted as a string.',
        'title' => 'Plain decimal',
      ],
      'plain_boolean' => static::nullableScalar('boolean') + [
        'description' => 'Plain boolean: An optional boolean input.',
        'title' => 'Plain boolean',
      ],
      'plain_timestamp' => static::isCoreSchemaPath()
        ? [
          'type' => ['string', 'null'],
          'format' => 'date-time',
          'description' => 'Plain timestamp: An optional timestamp input.',
          'title' => 'Plain timestamp',
        ]
        : [
          'type' => ['integer', 'null'],
          'description' => 'Plain timestamp: An optional timestamp input. A Unix timestamp in seconds.',
          'title' => 'Plain timestamp',
        ],
      'datetime_full' => [
        'type' => ['string', 'null'],
        'format' => 'date-time',
        'description' => 'Datetime full: A date and time input.',
        'title' => 'Datetime full',
      ],
      'datetime_date' => [
        'type' => ['string', 'null'],
        'format' => 'date',
        'description' => 'Datetime date: A date-only input.',
        'title' => 'Datetime date',
      ],
      // @todo The two paths disagree for an unmarked datetime: core's
      // DateTimeIso8601Normalizer says 'date' (its date-only field case) while
      // the legacy path says 'date-time', and the coercion subscriber re-emits
      // parsable values as full ISO 8601. The core-path 'date' advertisement
      // undersells what the input accepts; pinned here so a deliberate fix
      // shows up as a wire-contract change.
      'datetime_bare' => [
        'type' => ['string', 'null'],
        'format' => static::isCoreSchemaPath() ? 'date' : 'date-time',
        'description' => 'Datetime bare: A datetime input without a format marker.',
        'title' => 'Datetime bare',
      ],
      'enum_string' => static::nullableScalar('string') + [
        // Optional, so null is a listed value as well as a type (#3583072).
        'enum' => ['alpha', 'beta', 'gamma', NULL],
        'description' => 'Enum string: A choice-constrained string input.',
        'title' => 'Enum string',
      ],
      'enum_integer' => static::nullableScalar('integer') + [
        'enum' => [1, 2, 3, NULL],
        'description' => 'Enum integer: A choice-constrained integer input.',
        'title' => 'Enum integer',
      ],
      'multi_string' => [
        'type' => ['array', 'null'],
        'items' => ['type' => 'string'],
        'description' => 'Multi string: A multiple string input.',
        'title' => 'Multi string',
      ],
      'integer_list' => [
        'type' => ['array', 'null'],
        'description' => 'Integer list: A list of integers.',
        'items' => [
          'type' => 'integer',
          'description' => 'Integer item: An integer item.',
          'title' => 'Integer item',
        ],
        'title' => 'Integer list',
      ],
      'multi_datetime' => [
        'type' => ['array', 'null'],
        'items' => [
          'type' => 'string',
          'format' => 'date-time',
        ],
        'description' => 'Multi datetime: A multiple date and time input.',
        'title' => 'Multi datetime',
      ],
      'free_map' => [
        'type' => ['object', 'null'],
        'properties' => new \ArrayObject(),
        'description' => 'Free map: A map without declared properties.',
        'title' => 'Free map',
      ],
      'typed_map' => [
        'type' => ['object', 'null'],
        'properties' => [
          'title' => [
            'type' => 'string',
            'description' => 'Title: A required string property.',
            'title' => 'Title',
          ],
          'limit' => static::nullableScalar('integer') + [
            'description' => 'Limit: An integer property.',
            'title' => 'Limit',
          ],
          'verbose' => static::nullableScalar('boolean') + [
            'description' => 'Verbose: A boolean property.',
            'title' => 'Verbose',
          ],
        ],
        'required' => ['title'],
        'description' => 'Typed map: A map with typed properties.',
        'title' => 'Typed map',
      ],
      'multi_map' => [
        'type' => ['array', 'null'],
        'items' => [
          'type' => 'object',
          'properties' => [
            'sku' => static::nullableScalar('string') + [
              'description' => 'SKU: A stock keeping unit.',
              'title' => 'SKU',
            ],
            'quantity' => static::nullableScalar('integer') + [
              'description' => 'Quantity: A quantity.',
              'title' => 'Quantity',
            ],
          ],
        ],
        'description' => 'Multi map: A multiple map input.',
        'title' => 'Multi map',
      ],
      'bounded_maps' => [
        'type' => ['array', 'null'],
        'description' => 'Bounded maps: A count-bounded list of maps.',
        'items' => [
          'type' => 'object',
          'properties' => [
            'sku' => static::nullableScalar('string') + [
              'description' => 'SKU: A stock keeping unit.',
              'title' => 'SKU',
            ],
            'quantity' => static::nullableScalar('integer') + [
              'description' => 'Quantity: A quantity.',
              'title' => 'Quantity',
            ],
          ],
          'description' => 'Bounded item: A map item with two properties.',
          'title' => 'Bounded item',
        ],
        'maxItems' => 1,
        'title' => 'Bounded maps',
      ],
      'bounded_map' => [
        'type' => ['object', 'null'],
        'properties' => [
          'sku' => static::nullableScalar('string') + [
            'description' => 'SKU: A stock keeping unit.',
            'title' => 'SKU',
          ],
          'quantity' => static::nullableScalar('integer') + [
            'description' => 'Quantity: A quantity.',
            'title' => 'Quantity',
          ],
          'note' => static::nullableScalar('string') + [
            'description' => 'Note: A free text note.',
            'title' => 'Note',
          ],
        ],
        'minProperties' => 1,
        'maxProperties' => 2,
        'description' => 'Bounded map: A count-bounded map of optional properties.',
        'title' => 'Bounded map',
      ],
      'nested_map' => [
        'type' => ['object', 'null'],
        'properties' => [
          'inner' => [
            'type' => ['object', 'null'],
            'properties' => [
              'count' => static::nullableScalar('integer') + [
                'description' => 'Count: An integer inside the nested map.',
                'title' => 'Count',
              ],
            ],
            'description' => 'Inner map: A nested map property.',
            'title' => 'Inner map',
          ],
          'flags' => [
            'type' => ['array', 'null'],
            'description' => 'Flags: A list of booleans inside the map.',
            'items' => [
              'type' => 'boolean',
              'description' => 'Flag: A boolean item.',
              'title' => 'Flag',
            ],
            'title' => 'Flags',
          ],
        ],
        'description' => 'Nested map: A map holding a map and a list.',
        'title' => 'Nested map',
      ],
      'deep_variants' => [
        'type' => ['array', 'null'],
        'items' => [
          'type' => 'object',
          'properties' => [
            'name' => static::nullableScalar('string') + [
              'description' => 'Name: A string property.',
              'title' => 'Name',
            ],
            'sizes' => [
              'type' => ['array', 'null'],
              'description' => 'Sizes: A list of integers inside each item.',
              'items' => [
                'type' => 'integer',
                'description' => 'Size: An integer size.',
                'title' => 'Size',
              ],
              'title' => 'Sizes',
            ],
          ],
        ],
        'description' => 'Deep variants: A multiple map whose items hold a list.',
        'title' => 'Deep variants',
      ],
      // Both paths emit an untyped schema for 'any': every JSON value
      // validates, matching what the input accepts. This makes 'any' a true
      // upper bound, so refining it to a concrete type is always narrowing.
      // Core's normalizer annotates the untyped schema with a $comment; the
      // legacy path emits no keywords at all.
      'any_value' => (static::isCoreSchemaPath()
        ? ['$comment' => 'No schema is defined for property of type Drupal\Core\TypedData\Plugin\DataType\Any. See https://www.drupal.org/node/3424710 for information on implementing schemas in your program code.']
        : []) + [
          'description' => 'Any value: An input without a declared type.',
          'title' => 'Any value',
        ],
      'default_string' => static::nullableScalar('string') + [
        'default' => 'fallback',
        'description' => 'Default string: A string input with a default value.',
        'title' => 'Default string',
      ],
    };
  }

  /**
   * Tests the exact schema emitted for each matrix input.
   *
   * @param string $name
   *   The input name on schema_matrix_tool.
   *
   * @dataProvider schemaProvider
   */
  #[DataProvider('schemaProvider')]
  public function testInputSchema(string $name): void {
    $this->assertEquals($this->expectedSchema($name), $this->schema['properties'][$name]);
  }

  /**
   * Tests the root schema object shape.
   *
   * The root must forbid undeclared argument names (invokers validate
   * strictly against this), list exactly the required inputs, and cover
   * every declared input definition.
   */
  public function testRootSchemaShape(): void {
    $this->assertSame('object', $this->schema['type']);
    $this->assertFalse($this->schema['additionalProperties']);
    $this->assertSame(['required_string'], $this->schema['required']);

    $tool = $this->container->get('plugin.manager.tool')->createInstance('schema_matrix_tool');
    $this->assertSame(
      array_keys($tool->getInputDefinitions()),
      array_keys($this->schema['properties']),
    );
  }

}
