<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Matrix;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Exception\InputException;
use Drupal\tool\Tool\ToolInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Matrix tests for LLM-shaped values through the input transform pipeline.
 *
 * Each accepted case feeds a value in a shape an LLM plausibly produces —
 * numeric strings, JSON-encoded structures, bare scalars for lists, offset
 * datetime spellings — and asserts the exact typed value doExecute()
 * receives. Each rejected case feeds a value whose intent is ambiguous or
 * invalid and asserts it fails loudly instead of being silently guessed at.
 *
 * The fixed-point pass re-feeds every accepted case's expected value and
 * asserts it survives the pipeline unchanged: coercion must be idempotent,
 * or invokers replaying already-coerced values would corrupt them.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class InputCoercionMatrixTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Creates the matrix tool with its required input preset.
   */
  protected function createTool(): ToolInterface {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('schema_matrix_tool');
    $tool->setInputValue('required_string', 'present');
    return $tool;
  }

  /**
   * Executes the matrix tool with one input set and returns received values.
   *
   * @param string $name
   *   The input name.
   * @param mixed $value
   *   The value to set.
   *
   * @return array
   *   The values doExecute() received.
   */
  protected function executeWith(string $name, mixed $value): array {
    $tool = $this->createTool();
    $tool->setInputValue($name, $value);
    $tool->execute();
    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $tool->getResultMessage());
    return $result->getContextValues()['received'];
  }

  /**
   * Provides LLM-shaped values and the exact value the tool must receive.
   *
   * @return \Generator
   *   Cases of [input name, given value, expected received value].
   */
  public static function acceptedProvider(): \Generator {
    // Integers: numeric strings and integral floats become real integers.
    yield 'integer from numeric string' => ['plain_integer', '5', 5];
    yield 'integer from negative numeric string' => ['plain_integer', '-3', -3];
    yield 'integer from float-formatted string' => ['plain_integer', '5.0', 5];
    yield 'integer from integral float' => ['plain_integer', 5.0, 5];
    yield 'integer passes through' => ['plain_integer', 5, 5];
    yield 'integer from boolean true' => ['plain_integer', TRUE, 1];
    yield 'integer from boolean false' => ['plain_integer', FALSE, 0];
    yield 'range integer from string within bounds' => ['range_integer', '10', 10];

    // Floats: numeric strings become floats; a bare int is a valid float
    // value for typed data and passes through as-is.
    yield 'float from numeric string' => ['plain_float', '2.5', 2.5];
    yield 'float from integer-formatted string' => ['plain_float', '3', 3.0];
    yield 'float passes through' => ['plain_float', 2.5, 2.5];

    // Booleans: every spelling filter_var() recognizes, plus 0/1 integers.
    yield 'boolean from true string' => ['plain_boolean', 'true', TRUE];
    yield 'boolean from false string' => ['plain_boolean', 'false', FALSE];
    yield 'boolean from uppercase string' => ['plain_boolean', 'TRUE', TRUE];
    yield 'boolean from yes' => ['plain_boolean', 'yes', TRUE];
    yield 'boolean from no' => ['plain_boolean', 'no', FALSE];
    yield 'boolean from on' => ['plain_boolean', 'on', TRUE];
    yield 'boolean from off' => ['plain_boolean', 'off', FALSE];
    yield 'boolean from numeric string one' => ['plain_boolean', '1', TRUE];
    yield 'boolean from numeric string zero' => ['plain_boolean', '0', FALSE];
    yield 'boolean from integer one' => ['plain_boolean', 1, TRUE];
    yield 'boolean from integer zero' => ['plain_boolean', 0, FALSE];
    yield 'boolean from integral float one' => ['plain_boolean', 1.0, TRUE];
    yield 'boolean from integral float zero' => ['plain_boolean', 0.0, FALSE];
    yield 'boolean passes through' => ['plain_boolean', TRUE, TRUE];

    // Strings: a bare number is unambiguous and becomes its string
    // spelling; booleans stay for validation.
    yield 'string from integer' => ['plain_string', 5, '5'];
    yield 'string from float' => ['plain_string', 2.5, '2.5'];
    yield 'string from small float avoids exponent form' => ['plain_string', 0.0000001, '0.0000001'];
    // Fifteen significant digits: a float with an integer part comes back
    // as written, without the binary rounding error past them (#3583067).
    yield 'string from float with an integer part' => ['plain_string', 1234.567, '1234.567'];
    yield 'string from float with five integer digits' => ['plain_string', 99999.99, '99999.99'];
    yield 'string from float with eight integer digits' => ['plain_string', 12345678.123, '12345678.123'];
    yield 'string from negative float' => ['plain_string', -1234.567, '-1234.567'];
    yield 'string from float artifact collapses' => ['plain_string', 0.1 + 0.2, '0.3'];

    // Decimals travel as strings; bare JSON numbers are stringified.
    yield 'decimal from float' => ['plain_decimal', 19.99, '19.99'];
    yield 'decimal from integer' => ['plain_decimal', 5, '5'];
    yield 'decimal from small float avoids exponent form' => ['plain_decimal', 0.0000001, '0.0000001'];
    yield 'decimal from float with an integer part' => ['plain_decimal', 1234.567, '1234.567'];
    yield 'decimal from float with five integer digits' => ['plain_decimal', 99999.99, '99999.99'];
    yield 'decimal from float with eight integer digits' => ['plain_decimal', 12345678.123, '12345678.123'];
    yield 'decimal from negative float' => ['plain_decimal', -1234.567, '-1234.567'];
    yield 'decimal from float artifact collapses' => ['plain_decimal', 0.1 + 0.2, '0.3'];
    yield 'decimal string passes through' => ['plain_decimal', '7.25', '7.25'];

    // Timestamps: numeric strings, RFC 3339 strings, and integral floats.
    yield 'timestamp from numeric string' => ['plain_timestamp', '1700000000', 1700000000];
    yield 'timestamp from datetime string' => ['plain_timestamp', '2023-11-14T22:13:20+00:00', 1700000000];
    yield 'timestamp from integral float' => ['plain_timestamp', 1700000000.0, 1700000000];
    yield 'timestamp passes through' => ['plain_timestamp', 1700000000, 1700000000];

    // Datetime with a DateTime marker: offset spellings convert to the
    // offset-less UTC storage format.
    yield 'datetime from offset spelling' => ['datetime_full', '2026-11-14T09:32:00-07:00', '2026-11-14T16:32:00'];
    yield 'datetime from utc offset' => ['datetime_full', '2026-11-14T09:32:00+00:00', '2026-11-14T09:32:00'];
    yield 'datetime from zulu suffix' => ['datetime_full', '2026-11-14T09:32:00Z', '2026-11-14T09:32:00'];
    yield 'datetime from fractional seconds zulu' => [
      'datetime_full',
      '2026-11-14T09:32:00.500Z',
      '2026-11-14T09:32:00',
    ];
    yield 'datetime from fractional seconds offset' => [
      'datetime_full',
      '2026-11-14T09:32:00.000-07:00',
      '2026-11-14T16:32:00',
    ];
    yield 'datetime from microsecond precision' => [
      'datetime_full',
      '2026-11-14T09:32:00.123456+02:00',
      '2026-11-14T07:32:00',
    ];
    yield 'datetime from legacy offset spelling' => ['datetime_full', '2026-11-14T09:32:00-0700', '2026-11-14T16:32:00'];
    yield 'datetime in storage format passes through' => ['datetime_full', '2026-11-14T09:32:00', '2026-11-14T09:32:00'];
    yield 'datetime from space-separated offset spelling' => [
      'datetime_full',
      '2026-11-14 09:32:00+00:00',
      '2026-11-14T09:32:00',
    ];
    yield 'datetime from space-separated legacy offset' => [
      'datetime_full',
      '2026-11-14 09:32:00-0700',
      '2026-11-14T16:32:00',
    ];
    // Without an offset the space spelling names no absolute moment, so it
    // stays untouched like every other ambiguous spelling.
    yield 'offset-less space spelling left untouched' => ['datetime_full', '2026-11-14 09:32:00', '2026-11-14 09:32:00'];

    // Date-only marker: a bare date is already in the target format.
    yield 'date passes through' => ['datetime_date', '2026-11-14', '2026-11-14'];

    // Values a declared datetime target cannot resolve are never invented:
    // PHP would read whitespace as the current time and "tomorrow" as a
    // real date, neither of which the caller named. They pass through
    // untouched for the downstream consumer's own constraints to judge.
    yield 'relative expression never invented for datetime' => ['datetime_full', 'tomorrow', 'tomorrow'];
    yield 'whitespace never read as now for datetime' => ['datetime_full', '   ', '   '];
    yield 'bare date left untouched for datetime target' => ['datetime_full', '2026-11-14', '2026-11-14'];

    // Unmarked datetime: re-emitted as canonical ISO 8601, offset preserved.
    yield 'bare datetime from t-separated spelling' => [
      'datetime_bare',
      '2026-08-10T14:30:00+02:00',
      '2026-08-10T14:30:00+02:00',
    ];
    yield 'bare datetime from space-separated spelling' => [
      'datetime_bare',
      '2026-08-10 14:30:00 +0200',
      '2026-08-10T14:30:00+02:00',
    ];
    yield 'bare date left untouched for unmarked datetime' => ['datetime_bare', '2026-08-10', '2026-08-10'];
    // Purely numeric strings are ambiguous — PHP parses "2026" as a time of
    // day, not a year — so they pass through untouched.
    yield 'numeric string left untouched for unmarked datetime' => ['datetime_bare', '2026', '2026'];

    // Enums: case/whitespace near-misses snap; numeric strings coerce for
    // integer choices without the string snap interfering.
    yield 'string enum snaps case and whitespace' => ['enum_string', ' Alpha ', 'alpha'];
    yield 'string enum passes through' => ['enum_string', 'beta', 'beta'];
    yield 'integer enum from numeric string' => ['enum_integer', '2', 2];
    yield 'integer enum passes through' => ['enum_integer', 2, 2];

    // Multiple scalars: bare items are wrapped, JSON strings are decoded,
    // and decoded items still get per-item coercion.
    yield 'multiple string wraps bare item' => ['multi_string', 'x', ['x']];
    // Falsy scalars are valid single items and must be wrapped too; a
    // !empty() guard once skipped them (#3582999).
    yield 'multiple string wraps falsy string' => ['multi_string', '0', ['0']];
    yield 'integer list wraps falsy integer' => ['integer_list', 0, [0]];
    // A non-JSON string that merely starts with a brace is a valid single
    // string item, not a failed decode (#3582982).
    yield 'multiple string wraps brace-leading non-json' => ['multi_string', '{not json', ['{not json']];
    // An empty array is already a valid empty list; array_is_list() reports
    // TRUE for it, so it must not become a list of one empty item.
    yield 'empty array stays an empty list' => ['multi_string', [], []];
    yield 'empty array stays an empty list of maps' => ['multi_map', [], []];
    yield 'multiple string decodes json array' => ['multi_string', '["a","b"]', ['a', 'b']];
    yield 'multiple string passes array through' => ['multi_string', ['a', 'b'], ['a', 'b']];
    yield 'integer list decodes and coerces items' => ['integer_list', '["5","10",15]', [5, 10, 15]];
    yield 'integer list wraps bare numeric string' => ['integer_list', '7', [7]];
    yield 'integer list wraps bare integer' => ['integer_list', 7, [7]];

    // Multiple datetime: the marker constraint must reach each item, both
    // for a wrapped bare item and for decoded JSON items.
    yield 'multiple datetime wraps and converts bare item' => [
      'multi_datetime',
      '2026-11-14T09:32:00-07:00',
      ['2026-11-14T16:32:00'],
    ];
    yield 'multiple datetime decodes and converts json items' => [
      'multi_datetime',
      '["2026-11-14T09:32:00Z","2026-01-01T05:00:00-05:00"]',
      ['2026-11-14T09:32:00', '2026-01-01T10:00:00'],
    ];

    // Maps: JSON strings decode and members still coerce.
    yield 'typed map decodes json and coerces members' => [
      'typed_map',
      '{"title":"x","limit":"7","verbose":"true"}',
      ['title' => 'x', 'limit' => 7, 'verbose' => TRUE],
    ];
    yield 'typed map coerces members in place' => [
      'typed_map',
      ['title' => 'x', 'limit' => '7'],
      ['title' => 'x', 'limit' => 7],
    ];
    yield 'free map decodes json' => ['free_map', '{"a":1}', ['a' => 1]];
    yield 'free map passes through' => ['free_map', ['k' => 'v'], ['k' => 'v']];

    // Multiple maps: single property maps wrap, JSON arrays decode, and a
    // JSON object string cascades wrap-then-decode into one coerced item.
    yield 'multiple map wraps single property map' => [
      'multi_map',
      ['sku' => 'AB', 'quantity' => '2'],
      [['sku' => 'AB', 'quantity' => 2]],
    ];
    yield 'multiple map decodes json array of objects' => [
      'multi_map',
      '[{"sku":"a","quantity":"1"},{"sku":"b","quantity":2}]',
      [['sku' => 'a', 'quantity' => 1], ['sku' => 'b', 'quantity' => 2]],
    ];
    yield 'multiple map cascades wrap then decode for json object' => [
      'multi_map',
      '{"sku":"c","quantity":"3"}',
      [['sku' => 'c', 'quantity' => 3]],
    ];

    // Constraints describe the declared type, so a list bound lives on an
    // explicit ListInputDefinition: its Count restricts the list while the
    // item definition's own shape governs items, whose property count is
    // unrelated to the bound.
    yield 'bounded list takes one multi-property item' => [
      'bounded_maps',
      [['sku' => 'a', 'quantity' => '2']],
      [['sku' => 'a', 'quantity' => 2]],
    ];

    // Nested structures: JSON strings and bare items at inner levels are
    // handled by recursive child dispatch.
    yield 'nested map coerces json and bare items at depth' => [
      'nested_map',
      ['inner' => '{"count":"5"}', 'flags' => 'true'],
      ['inner' => ['count' => 5], 'flags' => [TRUE]],
    ];
    yield 'deep list in map in list coerces at every level' => [
      'deep_variants',
      ['name' => 'x', 'sizes' => '["1","2"]'],
      [['name' => 'x', 'sizes' => [1, 2]]],
    ];
    yield 'deep structure decodes from a single json string' => [
      'deep_variants',
      '[{"name":"x","sizes":["1",2]}]',
      [['name' => 'x', 'sizes' => [1, 2]]],
    ];

    // Any: no declared type means no coercion — values pass through.
    yield 'any keeps json strings verbatim' => ['any_value', '{"a":1}', '{"a":1}'];
    yield 'any keeps arrays verbatim' => ['any_value', ['a' => 1], ['a' => 1]];
    yield 'any keeps numeric strings verbatim' => ['any_value', '5', '5'];

    // Empty string means "no value" for optional non-string inputs.
    yield 'empty string nulls optional integer' => ['plain_integer', '', NULL];
    yield 'empty string nulls optional boolean' => ['plain_boolean', '', NULL];
    yield 'empty string nulls optional timestamp' => ['plain_timestamp', '', NULL];
    yield 'empty string nulls optional datetime' => ['datetime_full', '', NULL];
    yield 'empty string nulls optional map' => ['free_map', '', NULL];
    yield 'empty string nulls optional list' => ['multi_string', '', NULL];
    yield 'empty string survives for optional string' => ['plain_string', '', ''];

    // Explicit null is a valid value for every optional input.
    yield 'null accepted for optional integer' => ['plain_integer', NULL, NULL];
    yield 'null accepted for optional map' => ['free_map', NULL, NULL];
  }

  /**
   * Tests that an LLM-shaped value arrives in the definition's shape.
   *
   * @param string $name
   *   The input name.
   * @param mixed $given
   *   The value as an LLM might send it.
   * @param mixed $expected
   *   The exact value doExecute() must receive.
   *
   * @dataProvider acceptedProvider
   */
  #[DataProvider('acceptedProvider')]
  public function testLlmShapedValueIsCoerced(string $name, mixed $given, mixed $expected): void {
    $received = $this->executeWith($name, $given);
    $this->assertSame($expected, $received[$name]);
  }

  /**
   * Tests that already-coerced values survive the pipeline unchanged.
   *
   * @param string $name
   *   The input name.
   * @param mixed $given
   *   The original LLM-shaped value (unused; provider reuse).
   * @param mixed $expected
   *   The coerced value, which must be a fixed point of the pipeline.
   *
   * @dataProvider acceptedProvider
   */
  #[DataProvider('acceptedProvider')]
  public function testCoercedValuesAreFixedPoints(string $name, mixed $given, mixed $expected): void {
    $received = $this->executeWith($name, $expected);
    $this->assertSame($expected, $received[$name]);
  }

  /**
   * Provides values that must fail loudly rather than be guessed at.
   *
   * @return \Generator
   *   Cases of [input name, given value].
   */
  public static function rejectedProvider(): \Generator {
    yield 'fractional float for integer' => ['plain_integer', 7.5];
    yield 'word for integer' => ['plain_integer', 'abc'];
    yield 'array for integer' => ['plain_integer', ['5']];
    yield 'integer beyond one for boolean' => ['plain_boolean', 2];
    yield 'word for boolean' => ['plain_boolean', 'enabled'];
    yield 'range integer below minimum' => ['range_integer', '0'];
    yield 'range integer above maximum' => ['range_integer', 11];
    yield 'string below minimum length' => ['length_string', 'a'];
    yield 'string above maximum length' => ['length_string', 'far-too-long-value'];
    yield 'pattern mismatch' => ['regex_string', 'ABC-12'];
    yield 'malformed email' => ['email_type', 'not-an-email'];
    yield 'unknown string choice' => ['enum_string', 'delta'];
    yield 'empty string for choice stays for validation' => ['enum_string', ''];
    yield 'unknown integer choice from string' => ['enum_integer', '4'];
    yield 'unknown integer choice' => ['enum_integer', 4];
    yield 'time-carrying value for date-only target' => ['datetime_date', '2026-11-14T23:30:00-07:00'];
    yield 'word for datetime' => ['datetime_full', 'not-a-date'];
    yield 'word for timestamp' => ['plain_timestamp', 'not-a-date'];
    yield 'json object for integer list' => ['integer_list', '{"a":1}'];
    // Malformed JSON must not be silently decoded; the raw string reaches
    // map storage and surfaces as an InputException naming the input
    // (#3582981).
    yield 'malformed json for map stays a string' => ['typed_map', '{"title": tru'];
    // The Count declared on a ListInputDefinition binds the list itself
    // (#3583048).
    yield 'items beyond the count bound' => [
      'bounded_maps',
      [['sku' => 'a'], ['sku' => 'b']],
    ];
    // Item-level constraints must keep applying alongside the list-level ones.
    yield 'invalid member inside a count-bounded item' => [
      'bounded_maps',
      [['sku' => 'a', 'quantity' => 'not-a-number']],
    ];
    yield 'word for decimal' => ['plain_decimal', 'abc'];
    // NULL items in a list are always invalid — an item slot cannot be
    // "omitted"; it either holds a value or should not exist. (#3583007)
    yield 'null item in multiple string' => ['multi_string', [NULL]];
    yield 'null item in multiple map' => ['multi_map', [NULL]];
  }

  /**
   * Tests that ambiguous or invalid values are rejected, naming the input.
   *
   * Structurally un-storable values surface as an InputException from
   * setInputValue(); everything else must surface as a failed result. Both
   * must name the input so an invoker's correction loop can echo it.
   *
   * @param string $name
   *   The input name.
   * @param mixed $given
   *   The invalid value.
   *
   * @dataProvider rejectedProvider
   */
  #[DataProvider('rejectedProvider')]
  public function testInvalidValueFailsNamingTheInput(string $name, mixed $given): void {
    $tool = $this->createTool();
    try {
      $tool->setInputValue($name, $given);
    }
    catch (InputException $e) {
      $this->assertStringContainsString($name, $e->getMessage());
      return;
    }
    $tool->execute();
    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess(), sprintf(
      'Value %s for %s must be rejected.',
      var_export($given, TRUE),
      $name,
    ));
    $this->assertStringContainsString($name, (string) $result->getMessage());
  }

  /**
   * Tests that omitting the required input fails naming it.
   */
  public function testMissingRequiredInputFails(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('schema_matrix_tool');
    $tool->execute();
    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('required_string', (string) $result->getMessage());
  }

  /**
   * Tests that an unset input with a default executes with the default.
   */
  public function testDefaultValueAppliesWhenUnset(): void {
    $received = $this->executeWith('plain_string', 'anything');
    $this->assertSame('fallback', $received['default_string']);
  }

}
