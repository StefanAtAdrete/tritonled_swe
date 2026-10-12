<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Handle\ToolHandleStore;
use Drupal\tool\Normalizer\ToolDefinitionSerializer;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool_ai_connector\ToolAiConnectorInvoker;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Comprehensive tests for entity handle transforms across all type combos.
 *
 * Tests definition normalization (schema generation), input value transforms
 * (handle → entity), and output value transforms (entity → handle + hints)
 * for every supported input/output type including nested structures.
 *
 * @group tool_ai_connector
 */
#[RunTestsInSeparateProcesses]
final class ComprehensiveEntityHandleTransformTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'serialization',
    'ai',
    'tool',
    'tool_test',
    'tool_ai_connector',
    'node',
    'field',
    'text',
  ];

  /**
   * The tool manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected ToolManager $toolManager;

  /**
   * The tool definition serializer.
   *
   * @var \Drupal\tool\Normalizer\ToolDefinitionSerializer
   */
  protected ToolDefinitionSerializer $definitionSerializer;

  /**
   * The handle store.
   *
   * @var \Drupal\tool\Handle\ToolHandleStore
   */
  protected ToolHandleStore $handleStore;

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

    $this->toolManager = $this->container->get('plugin.manager.tool');
    $this->definitionSerializer = $this->container->get('tool.definition_serializer');
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Creates a saved test node.
   */
  private function createNode(string $title): Node {
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
    ]);
    $node->save();
    return $node;
  }

  /**
   * Finds a schema entry within a oneOf array by its type.
   *
   * @param array $one_of
   *   The oneOf array from a JSON schema.
   * @param string $type
   *   The type to find (e.g., 'array', 'object', 'string').
   *
   * @return array|null
   *   The matching schema entry, or NULL if not found.
   */
  private function findOneOfType(array $one_of, string $type): ?array {
    foreach ($one_of as $entry) {
      if (isset($entry['type']) && $entry['type'] === $type) {
        return $entry;
      }
    }
    return NULL;
  }

  /**
   * Asserts a schema represents a string type.
   *
   * Handles both formats:
   * - type: 'string'
   * - oneOf: [{type: 'string'}, ...]
   *
   * @param array $schema
   *   The schema to check.
   */
  private function assertStringType(array $schema): void {
    if (isset($schema['type'])) {
      $types = (array) $schema['type'];
      $this->assertContains('string', $types, 'Expected string type. Got: ' . json_encode($schema));
    }
    elseif (isset($schema['oneOf'])) {
      $this->assertNotNull($this->findOneOfType($schema['oneOf'], 'string'), 'Expected string in oneOf. Got: ' . json_encode($schema));
    }
    else {
      $this->fail("Schema has neither 'type' nor 'oneOf' for string check. Got: " . json_encode($schema));
    }
  }

  /**
   * Asserts a schema is nullable with the expected base type.
   *
   * Handles both nullable formats:
   * - type: ['array', 'null'] (our normalizer's format)
   * - oneOf: [{type: 'array', ...}, {type: 'null'}] (core's format)
   *
   * @param array $schema
   *   The schema to check.
   * @param string $expected_type
   *   The expected base type (e.g., 'array', 'object', 'string').
   */
  private function assertNullableType(array $schema, string $expected_type): void {
    if (isset($schema['type'])) {
      // type: ['array', 'null'] format.
      $this->assertContains($expected_type, (array) $schema['type']);
      $this->assertContains('null', (array) $schema['type']);
    }
    elseif (isset($schema['oneOf'])) {
      // oneOf: [{type: X}, {type: null}] format.
      $this->assertNotNull($this->findOneOfType($schema['oneOf'], $expected_type));
      $this->assertNotNull($this->findOneOfType($schema['oneOf'], 'null'));
    }
    else {
      $this->fail("Schema has neither 'type' nor 'oneOf' key for nullable {$expected_type}. Got: " . json_encode($schema));
    }
  }

  /**
   * Tests input definition normalization with the invoker active.
   *
   * Entity definitions should be rewritten to string (or array of strings
   * for multiple/list). Non-entity definitions should pass through unchanged.
   */
  public function testInputDefinitionNormalization(): void {
    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $schema = $this->definitionSerializer->normalizeInputSchema($tool);

    // Scalar types pass through.
    $this->assertSame('string', $schema['properties']['name']['type']);
    $this->assertSame('integer', $schema['properties']['count']['type']);
    // Optional boolean — nullable form differs by core version.
    $this->assertArrayHasKey('enabled', $schema['properties']);
    $this->assertNullableType($schema['properties']['enabled'], 'boolean');
    $this->assertNotContains('enabled', $schema['required'] ?? []);

    // Single entity → string with handle description.
    $this->assertSame('string', $schema['properties']['entity']['type']);
    $this->assertStringContainsString('handle', $schema['properties']['entity']['description']);

    // Multiple entities → array of strings (optional → nullable).
    $multiple_schema = $schema['properties']['multiple_entities'];
    $this->assertNullableType($multiple_schema, 'array');
    $items_schema = $multiple_schema['items'] ?? $this->findOneOfType($multiple_schema['oneOf'] ?? [], 'array')['items'] ?? NULL;
    $this->assertNotNull($items_schema);
    $this->assertStringType($items_schema);

    // List of entities → array of strings (optional → nullable).
    $list_schema = $schema['properties']['entity_list'];
    $this->assertNullableType($list_schema, 'array');
    $list_items = $list_schema['items'] ?? $this->findOneOfType($list_schema['oneOf'] ?? [], 'array')['items'] ?? NULL;
    $this->assertNotNull($list_items);
    $this->assertStringType($list_items);

    // Map with entity nested inside (optional → nullable).
    $metadata_schema = $schema['properties']['metadata'];
    $this->assertNullableType($metadata_schema, 'object');
    // Get the object schema from within the nullable wrapper.
    $meta_props = $metadata_schema['properties'] ?? NULL;
    if ($meta_props === NULL && isset($metadata_schema['oneOf'])) {
      $obj_schema = $this->findOneOfType($metadata_schema['oneOf'], 'object');
      $meta_props = $obj_schema['properties'] ?? NULL;
    }
    $this->assertNotNull($meta_props);
    $this->assertStringType($meta_props['label']);
    $this->assertStringType($meta_props['related_entity']);
    $related_desc = $meta_props['related_entity']['description'] ?? '';
    $this->assertStringContainsString('handle', $related_desc);
    // Tags list inside map stays as array (non-entity).
    $this->assertArrayHasKey('tags', $meta_props);

    // Required fields.
    $this->assertContains('name', $schema['required']);
    $this->assertContains('count', $schema['required']);
    $this->assertContains('entity', $schema['required']);
    $this->assertNotContains('enabled', $schema['required']);
    $this->assertNotContains('multiple_entities', $schema['required']);
    $this->assertNotContains('entity_list', $schema['required']);
    $this->assertNotContains('metadata', $schema['required']);
  }

  /**
   * Tests output definition normalization with the invoker active.
   */
  public function testOutputDefinitionNormalization(): void {
    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $output_schema = $this->definitionSerializer->normalizeOutputDefinitions($tool);

    // Scalar output (required by default).
    $this->assertSame('string', $output_schema['summary']['type']);

    // Single entity output → string (optional; nullable form differs by
    // core version).
    $this->assertArrayHasKey('result_entity', $output_schema);
    $this->assertNullableType($output_schema['result_entity'], 'string');
    $this->assertStringContainsString('handle', $output_schema['result_entity']['description']);

    // Multiple entity output → array of strings (optional → nullable).
    $multiple_out_schema = $output_schema['result_multiple'];
    $this->assertNullableType($multiple_out_schema, 'array');
    $multiple_items = $multiple_out_schema['items'] ?? $this->findOneOfType($multiple_out_schema['oneOf'] ?? [], 'array')['items'] ?? NULL;
    $this->assertNotNull($multiple_items);
    $this->assertStringType($multiple_items);

    // List of entities output → array of strings (optional → nullable).
    $list_out_schema = $output_schema['result_list'];
    $this->assertNullableType($list_out_schema, 'array');
    $list_out_items = $list_out_schema['items'] ?? $this->findOneOfType($list_out_schema['oneOf'] ?? [], 'array')['items'] ?? NULL;
    $this->assertNotNull($list_out_items);
    $this->assertStringType($list_out_items);

    // Map with entity nested inside (optional → nullable).
    $map_out_schema = $output_schema['result_map'];
    $this->assertNullableType($map_out_schema, 'object');
    $map_props = $map_out_schema['properties'] ?? $this->findOneOfType($map_out_schema['oneOf'] ?? [], 'object')['properties'] ?? NULL;
    $this->assertNotNull($map_props);
    $this->assertStringType($map_props['note']);
    $this->assertStringType($map_props['nested_entity']);
    // Verify handle description is present on the entity property.
    $nested_desc = $map_props['nested_entity']['description'] ?? '';
    $this->assertStringContainsString('handle', $nested_desc);
  }

  /**
   * Tests single entity input transform (handle → entity).
   */
  public function testSingleEntityInputTransform(): void {
    $node = $this->createNode('Test Node');
    $handle = $this->handleStore->store($node);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 1);
    $tool->setInputValue('entity', $handle);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $result->getMessage());
    // The entity was resolved and its title modified in doExecute.
    $result_entity = $result->getContextValues()['result_entity'] ?? NULL;
    $this->assertInstanceOf(Node::class, $result_entity);
    $this->assertSame('Output: Test Node', $result_entity->getTitle());
  }

  /**
   * Tests multiple entity input transform (array of handles).
   */
  public function testMultipleEntityInputTransform(): void {
    $node1 = $this->createNode('Multi 1');
    $node2 = $this->createNode('Multi 2');
    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 2);
    $tool->setInputValue('entity', $handle1);
    $tool->setInputValue('multiple_entities', [$handle1, $handle2]);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $result->getMessage());
    // The multiple entities should be returned as output.
    $this->assertCount(2, $result->getContextValues()['result_multiple']);
  }

  /**
   * Tests list entity input transform (ListInputDefinition with entity items).
   */
  public function testListEntityInputTransform(): void {
    $node1 = $this->createNode('List 1');
    $node2 = $this->createNode('List 2');
    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 2);
    $tool->setInputValue('entity', $handle1);
    $tool->setInputValue('entity_list', [$handle1, $handle2]);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $result->getMessage());
    // The list entities should be returned as output.
    $this->assertCount(2, $result->getContextValues()['result_list']);
  }

  /**
   * Tests entity nested in map input transform.
   */
  public function testMapNestedEntityInputTransform(): void {
    $node = $this->createNode('Map Entity');
    $handle = $this->handleStore->store($node);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 1);
    $tool->setInputValue('entity', $handle);
    $tool->setInputValue('metadata', [
      'label' => 'from map',
      'related_entity' => $handle,
      'tags' => ['a', 'b'],
    ]);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $result->getMessage());
    // The map output should contain the nested entity.
    $result_map = $result->getContextValues()['result_map'];
    $this->assertSame('from map', $result_map['note']);
    $this->assertInstanceOf(Node::class, $result_map['nested_entity']);
  }

  /**
   * Tests single entity output transform (entity → handle + hint).
   */
  public function testSingleEntityOutputTransform(): void {
    $node = $this->createNode('Output Single');
    $handle = $this->handleStore->store($node);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 1);
    $tool->setInputValue('entity', $handle);
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    $this->assertTrue($formatted->isSuccess(), (string) $formatted->getMessage());

    // Single entity output becomes a handle string.
    $result_entity = $formatted->getContextValues()['result_entity'];
    $this->assertIsString($result_entity);
    $this->assertStringStartsWith('handle:', $result_entity);

    // Hint should contain the handle and entity metadata.
    $hints = $formatted->getHints();
    $this->assertNotEmpty($hints);
    $hint_text = implode("\n", $hints);
    $this->assertStringContainsString($result_entity, $hint_text);
    $this->assertStringContainsString('type: node', $hint_text);
  }

  /**
   * Tests multiple entity output transform (batch hint).
   */
  public function testMultipleEntityOutputTransform(): void {
    $node1 = $this->createNode('Batch 1');
    $node2 = $this->createNode('Batch 2');
    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 2);
    $tool->setInputValue('entity', $handle1);
    $tool->setInputValue('multiple_entities', [$handle1, $handle2]);
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    $this->assertTrue($formatted->isSuccess(), (string) $formatted->getMessage());
    $result_multiple = $formatted->getContextValues()['result_multiple'] ?? NULL;

    // Should be array of handle strings.
    $this->assertIsArray($result_multiple);
    $this->assertCount(2, $result_multiple);
    foreach ($result_multiple as $h) {
      $this->assertIsString($h);
      $this->assertStringStartsWith('handle:', $h);
    }

    // Should have ONE batch hint, not per-entity hints.
    $hints = $formatted->getHints();
    $hint_text = implode("\n", $hints);
    $this->assertStringContainsString('contains 2 entity handle(s):', $hint_text);
    $this->assertSame(
      1,
      substr_count($hint_text, 'Pass these handles to subsequent tool calls'),
    );
  }

  /**
   * Tests list entity output transform (list output definition batch hint).
   */
  public function testListEntityOutputTransform(): void {
    $node1 = $this->createNode('ListOut 1');
    $node2 = $this->createNode('ListOut 2');
    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 2);
    $tool->setInputValue('entity', $handle1);
    $tool->setInputValue('entity_list', [$handle1, $handle2]);
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    $this->assertTrue($formatted->isSuccess(), (string) $formatted->getMessage());
    $result_list = $formatted->getContextValues()['result_list'] ?? NULL;

    // Should be array of handle strings.
    $this->assertIsArray($result_list);
    $this->assertCount(2, $result_list);
    foreach ($result_list as $h) {
      $this->assertIsString($h);
      $this->assertStringStartsWith('handle:', $h);
    }

    // Batch hint for the list output.
    $hint_text = implode("\n", $formatted->getHints());
    $this->assertStringContainsString('`result_list` contains 2 entity handle(s):', $hint_text);
  }

  /**
   * Tests entity nested in map output transform.
   */
  public function testMapNestedEntityOutputTransform(): void {
    $node = $this->createNode('Map Nested');
    $handle = $this->handleStore->store($node);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 1);
    $tool->setInputValue('entity', $handle);
    $tool->setInputValue('metadata', [
      'label' => 'nested test',
      'related_entity' => $handle,
    ]);
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    $this->assertTrue($formatted->isSuccess(), (string) $formatted->getMessage());
    $result_map = $formatted->getContextValues()['result_map'] ?? NULL;

    // Map structure preserved, entity replaced with handle.
    $this->assertIsArray($result_map);
    $this->assertSame('nested test', $result_map['note']);
    $this->assertIsString($result_map['nested_entity']);
    $this->assertStringStartsWith('handle:', $result_map['nested_entity']);

    // Hint should reference the nested entity handle.
    $hint_text = implode("\n", $formatted->getHints());
    $this->assertStringContainsString($result_map['nested_entity'], $hint_text);
  }

  /**
   * Tests full round-trip: handles in → entities processed → handles out.
   */
  public function testFullRoundTrip(): void {
    $node1 = $this->createNode('Round Trip 1');
    $node2 = $this->createNode('Round Trip 2');
    $node3 = $this->createNode('Round Trip 3');
    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);
    $handle3 = $this->handleStore->store($node3);

    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'full test');
    $tool->setInputValue('count', 3);
    $tool->setInputValue('entity', $handle1);
    $tool->setInputValue('multiple_entities', [$handle1, $handle2]);
    $tool->setInputValue('entity_list', [$handle2, $handle3]);
    $tool->setInputValue('metadata', [
      'label' => 'round trip',
      'related_entity' => $handle3,
      'tags' => ['x', 'y'],
    ]);
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    $this->assertTrue($formatted->isSuccess(), (string) $formatted->getMessage());

    $values = $formatted->getContextValues();

    // Scalar output.
    $this->assertSame('Processed: full test, count=3', $values['summary']);

    // Single entity — handle string.
    $this->assertStringStartsWith('handle:', $values['result_entity']);
    $resolved = $this->handleStore->resolve($values['result_entity']);
    // The entity should have been modified by doExecute.
    $this->assertStringContainsString('Round Trip 1', $resolved->getTitle());

    // Multiple entities — array of handles.
    $this->assertCount(2, $values['result_multiple']);

    // List entities — array of handles.
    $this->assertCount(2, $values['result_list']);

    // Map with nested entity — handle in map.
    $this->assertSame('round trip', $values['result_map']['note']);
    $this->assertStringStartsWith('handle:', $values['result_map']['nested_entity']);

    // All handles should be resolvable.
    foreach ($values['result_multiple'] as $h) {
      $this->assertInstanceOf(Node::class, $this->handleStore->resolve($h));
    }
    foreach ($values['result_list'] as $h) {
      $this->assertInstanceOf(Node::class, $this->handleStore->resolve($h));
    }
    $this->assertInstanceOf(
      Node::class,
      $this->handleStore->resolve($values['result_map']['nested_entity']),
    );
  }

  /**
   * Tests handle-prefixed strings pass through untouched on string inputs.
   *
   * Regression test: the single-value input path previously ran handle
   * resolution on every input, so a plain string input whose value merely
   * started with "handle:" either threw InvalidHandleException (invalid
   * suffix) or was silently replaced with an entity object (live handle).
   * Only entity-typed inputs are subject to handle resolution.
   */
  public function testHandlePrefixedStringInputPassesThrough(): void {
    // A handle-prefixed string that is not a valid handle must not throw
    // on a string-typed input.
    $tool = $this->toolManager->createInstance(
      'greeting_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool->setInputValue('name', 'handle: with care');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $result->getMessage());
    $this->assertSame('Hello, handle: with care!', $tool->getOutputValue('message'));

    // A live, valid handle on a string-typed input must stay a string
    // rather than being upcast to the referenced entity.
    $node = $this->createNode('Handle String Passthrough');
    $handle = $this->handleStore->store($node);

    $tool2 = $this->toolManager->createInstance(
      'greeting_tool',
      [],
      ToolAiConnectorInvoker::create(),
    );
    $tool2->setInputValue('name', $handle);
    $tool2->execute();

    $result2 = $tool2->getResult();
    $this->assertTrue($result2->isSuccess(), (string) $result2->getMessage());
    $this->assertSame("Hello, {$handle}!", $tool2->getOutputValue('message'));
  }

}
