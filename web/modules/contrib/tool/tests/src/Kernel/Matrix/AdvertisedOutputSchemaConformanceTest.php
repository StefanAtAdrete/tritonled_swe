<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Matrix;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\tool\Traits\SchemaSampleGeneratorTrait;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\Tool\ToolInterface;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Round-trip property: every output value must match its advertised schema.
 *
 * The mirror of AdvertisedSchemaAcceptanceTest, which asserts the same
 * property for inputs. Invokers such as the MCP bridge validate
 * structuredContent against the advertised outputSchema, and the spec
 * requires servers to "provide structured results that conform to this
 * schema", so a value whose type or format the schema describes differently
 * is unusable over that wire.
 *
 * Every discovered tool with output definitions is executed with
 * schema-conforming inputs, and each value of getFormattedResult() is
 * validated against the schema normalized for the same invoker, formats
 * included. Only the formatted result is checked: that is what invokers
 * send, while getResult() keeps the PHP values.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class AdvertisedOutputSchemaConformanceTest extends KernelTestBase {

  use SchemaSampleGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'tool_test',
    'node',
    'field',
    'text',
  ];

  /**
   * A handle for inputs whose values cannot be derived from the schema.
   */
  protected string $nodeHandle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    $node = Node::create([
      'type' => 'article',
      'title' => 'Sample node',
    ]);
    $node->save();
    $this->nodeHandle = $this->container->get('tool.handle_store')->store($node);
  }

  /**
   * Tools excluded from the property, with the reason.
   *
   * @return array
   *   Reasons keyed by tool ID.
   */
  protected function skippedTools(): array {
    return [
      // Requires a nested entity graph inside required map properties;
      // its pipeline is covered by ToolComplexEventTransformTest.
      'complex_entity_event_tool' => 'nested required entities',
      // A fixture that deliberately violates the refiner contract: setting
      // its dependency input throws by design, which RefinerContractTest
      // asserts.
      'refiner_contract_tool' => 'throws on set by design',
      // Its constructor throws by design, to prove one broken plugin does
      // not hide the rest from tool:list and tool:info.
      'broken_constructor_tool' => 'throws on create by design',
    ];
  }

  /**
   * Values for inputs whose schema cannot produce them generically.
   *
   * The tools run under an invoker with the EntitiesAsHandles capability,
   * so entity inputs are handle strings, matching the MCP bridge.
   *
   * @return array
   *   Override values keyed by tool ID, then input name.
   */
  protected function sampleOverrides(): array {
    return [
      'comprehensive_types_tool' => [
        'entity' => $this->nodeHandle,
        'multiple_entities' => [$this->nodeHandle],
        'entity_list' => [$this->nodeHandle],
      ],
      'entity_list_input_output_tool' => [
        'entities' => [$this->nodeHandle],
      ],
      'refining_tool' => [
        'bundle' => 'article',
      ],
      'multi_dependency_refining_tool' => [
        'bundle' => 'article',
        'field' => 'title',
      ],
    ];
  }

  /**
   * Tests that the formatted outputs of every tool validate against the schema.
   */
  public function testOutputsMatchTheirAdvertisedSchema(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $serializer = $this->container->get('tool.definition_serializer');
    $invoker = new Invoker('tool_test', InvokerCapability::EntitiesAsHandles);
    $skipped = $this->skippedTools();
    $failures = [];
    $covered = 0;

    foreach (array_keys($manager->getDefinitions()) as $tool_id) {
      if (isset($skipped[$tool_id])) {
        continue;
      }
      $tool = $manager->createInstance($tool_id, [], $invoker);
      $schemas = $serializer->normalizeOutputDefinitions($tool);
      if ($schemas === []) {
        continue;
      }
      if (!$this->executeWithSampleInputs($tool, $serializer->normalizeInputSchema($tool))) {
        continue;
      }

      $values = $tool->getFormattedResult()->getContextValues();
      foreach ($schemas as $name => $schema) {
        if (!array_key_exists($name, $values)) {
          continue;
        }
        $covered++;
        $errors = $this->validationErrors($values[$name], $schema);
        if ($errors !== []) {
          $failures[] = sprintf(
            "%s.%s sent %s, which does not match its schema %s:\n  %s",
            $tool_id,
            $name,
            json_encode($values[$name], JSON_UNESCAPED_SLASHES),
            json_encode($schema, JSON_UNESCAPED_SLASHES),
            implode("\n  ", $errors),
          );
        }
      }
    }

    $this->assertSame([], $failures, implode("\n", $failures));
    // Guard against silently covering nothing if discovery changes.
    $this->assertGreaterThan(10, $covered);
  }

  /**
   * Tests the exact wire spelling of each converted output.
   *
   * The sweep proves conformance. This pins the values, so a conversion
   * that happens to validate but changes meaning (a shifted timezone, a
   * rounded decimal) is still caught.
   */
  public function testConvertedOutputsKeepTheirValue(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $tool = $manager->createInstance('timestamp_output_tool');
    $tool->execute();

    $values = $tool->getFormattedResult()->getContextValues();
    $timestamp = version_compare(\Drupal::VERSION, '11.1', '>=') ? '2026-08-01T17:55:07+00:00' : 1785606907;
    $this->assertSame([
      'occurred_at' => $timestamp,
      'amount' => '19.99',
      'scheduled_for' => '2026-08-01T12:35:07+00:00',
      'summary' => ['last_seen' => $timestamp],
    ], $values);

    // The PHP values are untouched for callers that compute with them.
    $this->assertSame(1785606907, $tool->getOutputValue('occurred_at'));
    $this->assertSame(19.99, $tool->getOutputValue('amount'));
  }

  /**
   * Tests that an empty map is sent as an object, at any depth.
   *
   * Covered for optional and required maps, top-level, as list items and as
   * declared properties of a map. A required map may be empty (#3583069).
   */
  public function testEmptyMapOutputEncodesAsObject(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $tool = $manager->createInstance('empty_map_output_tool');
    $tool->execute();

    $this->assertSame(
      '{"settings":{},"entries":[{}],"options":{},"required_settings":{},"required_options":{},"document":{"settings":{},"options":{},"entries":[{}]}}',
      json_encode($tool->getFormattedResult()->getContextValues()),
    );
    // The PHP values are untouched for callers that compute with them.
    $this->assertSame([], $tool->getOutputValue('settings'));
    $this->assertSame([[]], $tool->getOutputValue('entries'));
    $this->assertSame([], $tool->getOutputValue('options'));
    $this->assertSame([], $tool->getOutputValue('required_settings'));
    $this->assertSame(['settings' => [], 'options' => [], 'entries' => [[]]], $tool->getOutputValue('document'));
  }

  /**
   * Tests that a map keyed 0, 1, 2 in order is sent as an object.
   *
   * PHP writes such an array as a JSON array, like an empty one; the schema
   * says object (#3583073). A list keeps its array.
   */
  public function testListKeyedMapOutputEncodesAsObject(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $tool = $manager->createInstance('list_keyed_map_output_tool');
    $tool->execute();

    $this->assertSame(
      '{"settings":{"0":"a","1":"b"},"options":{"0":"x"},"document":{"settings":{"0":"a","1":"b"}},"entries":[{"0":"a","1":"b"}],"tags":["a","b"]}',
      json_encode($tool->getFormattedResult()->getContextValues()),
    );
    // The PHP values are untouched for callers that compute with them.
    $this->assertSame(['a', 'b'], $tool->getOutputValue('settings'));
    $this->assertSame([['a', 'b']], $tool->getOutputValue('entries'));
  }

  /**
   * Sets schema-conforming inputs on the tool and executes it.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool to execute.
   * @param array $input_schema
   *   The tool's normalized input schema.
   *
   * @return bool
   *   TRUE when the tool ran successfully, FALSE when it could not be given
   *   conforming inputs or reported a failure, in which case it has no
   *   outputs to check.
   */
  protected function executeWithSampleInputs(ToolInterface $tool, array $input_schema): bool {
    $tool_id = $tool->getPluginId();
    $overrides = $this->sampleOverrides()[$tool_id] ?? [];
    $properties = $input_schema['properties'];
    if ($properties instanceof \ArrayObject) {
      $properties = $properties->getArrayCopy();
    }

    foreach ($properties as $name => $property_schema) {
      try {
        $value = array_key_exists($name, $overrides)
          ? $overrides[$name]
          : $this->sampleFromSchema((array) $property_schema, "$tool_id.$name");
        $tool->setInputValue($name, $value);
      }
      catch (\Exception) {
        return FALSE;
      }
    }

    try {
      $tool->execute();
    }
    catch (\Throwable) {
      return FALSE;
    }
    return $tool->getResult()->isSuccess();
  }

  /**
   * Validates a value against a schema as a client would, formats included.
   *
   * Both sides go through JSON so the check sees what the wire carries.
   *
   * @param mixed $value
   *   The formatted output value.
   * @param array $schema
   *   The normalized output schema.
   *
   * @return string[]
   *   Validation error messages, empty when the value conforms.
   */
  protected function validationErrors(mixed $value, array $schema): array {
    $data = json_decode(json_encode($value, JSON_THROW_ON_ERROR), FALSE, 512, JSON_THROW_ON_ERROR);
    $validator = new Validator();
    $validator->validate($data, json_decode(json_encode($schema, JSON_THROW_ON_ERROR), FALSE, 512, JSON_THROW_ON_ERROR));
    return array_map(
      static fn (array $error): string => ($error['property'] !== '' ? $error['property'] . ': ' : '') . $error['message'],
      $validator->getErrors(),
    );
  }

}
