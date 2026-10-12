<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Handle;

use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Handle\EntityHandleTransformer;
use Drupal\tool\Handle\InvalidHandleException;
use Drupal\tool\Handle\ToolHandleStore;
use Drupal\tool\Handle\UnexpectedHandleValueException;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the EntityHandleTransformer service.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\Handle\EntityHandleTransformer
 */
#[RunTestsInSeparateProcesses]
class EntityHandleTransformerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'node',
    'user',
    'system',
    'field',
    'text',
  ];

  /**
   * The entity handle transformer.
   *
   * @var \Drupal\tool\Handle\EntityHandleTransformer
   */
  protected EntityHandleTransformer $transformer;

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

    $this->transformer = $this->container->get('tool.entity_handle_transformer');
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Tests transformOutput stores entity and returns handle.
   *
   * @covers ::transformOutput
   */
  public function testTransformOutputStoresEntity(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Node',
    ]);
    $node->save();

    $result = $this->transformer->transformOutput('loaded_entity', $node);

    // Should return a HandleTransformResult.
    $this->assertNotNull($result);
    $this->assertTrue($this->handleStore->isHandle($result->value));

    // Entity transport metadata should not be injected into entity state.
    $this->assertFalse(isset($node->_tool_handle));
    $this->assertArrayNotHasKey('_tool_handle', $node->toArray());

    // Handle should resolve back to the entity.
    $resolved = $this->handleStore->resolve($result->value);
    $this->assertEquals($node->id(), $resolved->id());

    // Hint should be present with output name.
    $this->assertNotNull($result->hint);
    $this->assertStringContainsString('`' . $result->value . '`', $result->hint);
    $this->assertStringContainsString('type: node', $result->hint);
    $this->assertStringContainsString('loaded_entity', $result->hint);
  }

  /**
   * Tests transformOutput ignores config entities.
   *
   * @covers ::transformOutput
   */
  public function testTransformOutputIgnoresConfigEntities(): void {
    $node_type = NodeType::load('article');

    $result = $this->transformer->transformOutput('node_type', $node_type);
    $this->assertNull($result);
  }

  /**
   * Tests transformOutput reuses a handle for the same entity object.
   *
   * @covers ::transformOutput
   */
  public function testTransformOutputReusesHandleForSameEntityObject(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test Node',
    ]);
    $node->save();

    // First output assigns a handle.
    $result1 = $this->transformer->transformOutput('entity', $node);
    $first_handle = $result1->value;

    $node->setTitle('Modified');

    $result2 = $this->transformer->transformOutput('entity', $node);
    $second_handle = $result2->value;

    $this->assertEquals($first_handle, $second_handle);

    $resolved = $this->handleStore->resolve($first_handle);
    $this->assertEquals('Modified', $resolved->getTitle());
  }

  /**
   * Tests resolveInput resolves handle to entity.
   *
   * @covers ::resolveInput
   */
  public function testResolveInputResolvesEntity(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Stored Node',
    ]);
    $node->save();

    // Store the entity.
    $handle = $this->handleStore->store($node);

    $resolved = $this->transformer->resolveInput($handle);

    // Should be the resolved entity.
    $this->assertEquals($node->id(), $resolved->id());

    // Transport metadata should not be injected into entity state.
    $this->assertFalse(isset($resolved->_tool_handle));
    $this->assertArrayNotHasKey('_tool_handle', $resolved->toArray());

    // Returning the same resolved object should preserve the original handle.
    $resolved->setTitle('Updated through handle');
    $result = $this->transformer->transformOutput('entity', $resolved);
    $this->assertEquals($handle, $result->value);
    $stored = $this->handleStore->resolve($handle);
    $this->assertEquals('Updated through handle', $stored->getTitle());
  }

  /**
   * Tests resolveInput rejects malformed handle strings.
   *
   * @covers ::resolveInput
   */
  public function testResolveInputRejectsMalformedHandle(): void {
    $this->expectException(InvalidHandleException::class);
    $this->transformer->resolveInput('handle:not-a-uuid');
  }

  /**
   * Tests resolveInput rejects handles that do not reference content entities.
   *
   * @covers ::resolveInput
   */
  public function testResolveInputRejectsNonContentEntityHandles(): void {
    $handle = $this->handleStore->store(['foo' => 'bar']);

    $this->expectException(UnexpectedHandleValueException::class);
    $this->transformer->resolveInput($handle);
  }

  /**
   * Tests resolveInput rejects handles that reference config entities.
   *
   * @covers ::resolveInput
   */
  public function testResolveInputRejectsConfigEntityHandles(): void {
    $handle = $this->handleStore->store(NodeType::load('article'));

    $this->expectException(UnexpectedHandleValueException::class);
    $this->transformer->resolveInput($handle);
  }

  /**
   * Tests resolveInput ignores non-handle strings.
   *
   * @covers ::resolveInput
   */
  public function testResolveInputIgnoresNonHandles(): void {
    $value = $this->transformer->resolveInput('just a regular string');
    $this->assertEquals('just a regular string', $value);
  }

  /**
   * Tests transformOutput ignores non-entity values.
   *
   * @covers ::transformOutput
   */
  public function testTransformOutputIgnoresNonEntities(): void {
    $result = $this->transformer->transformOutput('message', 'plain string');
    $this->assertNull($result);
  }

  /**
   * Tests hint includes new/unsaved indicator.
   *
   * @covers ::transformOutput
   */
  public function testHintForNewEntity(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Unsaved Node',
    ]);
    // Do not save the entity so it remains new.
    $result = $this->transformer->transformOutput('entity', $node);

    $this->assertStringContainsString('status: new, unsaved', $result->hint);
    $this->assertStringNotContainsString('id:', $result->hint);
  }

  /**
   * Tests hint includes entity metadata for saved entity.
   *
   * @covers ::transformOutput
   */
  public function testHintForSavedEntity(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Saved Node',
    ]);
    $node->save();

    $result = $this->transformer->transformOutput('entity', $node);

    $this->assertStringContainsString('id: ' . $node->id(), $result->hint);
    $this->assertStringContainsString('bundle: article', $result->hint);
    $this->assertStringContainsString('language:', $result->hint);
    $this->assertStringNotContainsString('new, unsaved', $result->hint);
  }

  /**
   * Tests entity input definitions are transformed to handle strings.
   *
   * @covers ::transformInputDefinition
   */
  public function testTransformInputDefinition(): void {
    $definitions = [
      new InputDefinition('entity', 'Loaded entity', 'The entity to update.', FALSE),
      new EntityInputDefinition('node', 'Loaded entity', 'The entity to update.', FALSE),
    ];

    foreach ($definitions as $definition) {
      $result = $this->transformer->transformInputDefinition($definition);

      $this->assertInstanceOf(InputDefinition::class, $result);
      $this->assertEquals('string', $result->getDataType());
      $this->assertEquals('Loaded entity', (string) $result->getLabel());
      $this->assertSame('The entity to update. (pass a handle from a previous tool call)', (string) $result->getDescription());
      $this->assertFalse($result->isRequired());
    }
  }

  /**
   * Tests a definition with an empty description gets only the handle note.
   *
   * @covers ::transformInputDefinition
   * @covers ::transformOutputDefinition
   */
  public function testTransformedDescriptionsWithoutOriginalDescription(): void {
    $input = new EntityInputDefinition('node', 'Loaded entity', '');
    $this->assertSame('(pass a handle from a previous tool call)', (string) $this->transformer->transformInputDefinition($input)->getDescription());

    $output = new EntityOutputDefinition('node', 'Loaded entity', '');
    $this->assertSame('(returns a handle for use in subsequent tool calls)', (string) $this->transformer->transformOutputDefinition($output)->getDescription());
  }

  /**
   * Tests transformed handle descriptions are not HTML-escaped.
   *
   * Wrapping the assembled description in a TranslatableMarkup '@'
   * placeholder ran it through Html::escape(), so apostrophes reached
   * LLM-facing schemas as &#039; on every invoker path.
   *
   * @covers ::transformInputDefinition
   * @covers ::transformOutputDefinition
   */
  public function testTransformedDescriptionsAreNotHtmlEscaped(): void {
    $input = new EntityInputDefinition(
      'node',
      'Token Data',
      "Avoid using 'recipient' & <keys> here.",
      FALSE,
    );
    $description = (string) $this->transformer->transformInputDefinition($input)->getDescription();
    $this->assertStringContainsString("'recipient' & <keys>", $description);
    $this->assertStringNotContainsString('&#039;', $description);
    $this->assertStringNotContainsString('&amp;', $description);

    $output = new EntityOutputDefinition(
      'node',
      'Result',
      "The node's result.",
    );
    $description = (string) $this->transformer->transformOutputDefinition($output)->getDescription();
    $this->assertStringContainsString("node's result", $description);
    $this->assertStringNotContainsString('&#039;', $description);
  }

  /**
   * Tests entity output definitions are transformed to handle strings.
   *
   * @covers ::transformOutputDefinition
   */
  public function testTransformOutputDefinition(): void {
    $definition = new EntityOutputDefinition(
      'node',
      'Loaded entity',
      'The loaded node.',
    );

    $result = $this->transformer->transformOutputDefinition($definition);

    $this->assertInstanceOf(OutputDefinition::class, $result);
    $this->assertNotInstanceOf(InputDefinition::class, $result);
    $this->assertEquals('string', $result->getDataType());
    $this->assertEquals('Loaded entity', (string) $result->getLabel());
    $this->assertSame('The loaded node. (returns a handle for use in subsequent tool calls)', (string) $result->getDescription());
    $this->assertTrue($result->isRequired());
  }

  /**
   * Tests the item of an entity list carries the handle note.
   *
   * The transform is local to the definition, so a list is left alone and
   * the normalizer transforms its item on its own. An item without a label
   * or description, like the item of a list converted from a multiple
   * definition (#3583049), becomes a handle string described by the handle
   * note alone, with no label. An item that describes itself keeps its label
   * and gets the note appended, as a top-level entity does.
   *
   * @covers ::transformInputDefinition
   * @covers ::transformOutputDefinition
   */
  public function testEntityListItemCarriesHandleNote(): void {
    $input = ListInputDefinition::fromMultiple(new EntityInputDefinition('node', 'Nodes', 'Some nodes.', FALSE, TRUE));
    $this->assertNull($this->transformer->transformInputDefinition($input));
    $item = $this->transformer->transformInputDefinition($input->getItemDefinition());
    $this->assertInstanceOf(InputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());
    $this->assertSame('', (string) $item->getLabel());
    $this->assertSame('(pass a handle from a previous tool call)', (string) $item->getDescription());
    $this->assertTrue($item->isRequired());
    $this->assertSame('entity:node', $input->getItemDefinition()->getDataType(), 'The original item is untouched.');

    $output = OutputDefinition::fromContextDefinition(new EntityContextDefinition('node', 'Nodes', multiple: TRUE, description: 'Some nodes.'), 'nodes');
    $this->assertInstanceOf(ListOutputDefinition::class, $output);
    $this->assertNull($this->transformer->transformOutputDefinition($output));
    $item = $this->transformer->transformOutputDefinition($output->getItemDefinition());
    $this->assertInstanceOf(OutputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());
    $this->assertSame('', (string) $item->getLabel());
    $this->assertSame('(returns a handle for use in subsequent tool calls)', (string) $item->getDescription());

    $described = new ListInputDefinition('Nodes', 'Some nodes.', item_definition: new EntityInputDefinition('node', 'Node', 'A node.'));
    $this->assertNull($this->transformer->transformInputDefinition($described));
    $item = $this->transformer->transformInputDefinition($described->getItemDefinition());
    $this->assertInstanceOf(InputDefinition::class, $item);
    $this->assertSame('Node', (string) $item->getLabel());
    $this->assertSame('A node. (pass a handle from a previous tool call)', (string) $item->getDescription());
    $this->assertNull($this->transformer->transformInputDefinition(new ListInputDefinition('Names', 'Some names.', item_definition: new InputDefinition('string', '', ''))));
  }

  /**
   * Tests non-content-entity definitions are not transformed.
   *
   * @covers ::transformInputDefinition
   * @covers ::transformOutputDefinition
   */
  public function testNonContentEntityDefinitionsAreNotTransformed(): void {
    $input = new InputDefinition('string', 'Message', 'A plain string.');
    $output = new OutputDefinition('string', 'Message', 'A plain string.');
    $config_entity_input = new EntityInputDefinition('node_type', 'Node type', 'A node type.');
    $config_entity_output = new EntityOutputDefinition('node_type', 'Node type', 'A node type.');

    $this->assertNull($this->transformer->transformInputDefinition($input));
    $this->assertNull($this->transformer->transformOutputDefinition($output));
    $this->assertNull($this->transformer->transformInputDefinition($config_entity_input));
    $this->assertNull($this->transformer->transformOutputDefinition($config_entity_output));
  }

}
