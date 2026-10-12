<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Handle;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Handle\ToolHandleStoreInterface;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests list-shaped entity inputs through EntityHandleTransformSubscriberBase.
 *
 * The base class resolves single values only and leaves list walking to
 * RecursiveToolValueTransformSubscriber and list wrapping to
 * InputTypeCoercionSubscriber. Each test covers one input shape a connector
 * can receive for a multiple or list entity input.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
final class EntityHandleTransformSubscriberTest extends KernelTestBase {

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
   * The tool manager.
   */
  private ToolManager $toolManager;

  /**
   * The handle store.
   */
  private ToolHandleStoreInterface $handleStore;

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
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Tests an array of handles for a multiple entity input.
   */
  public function testMultipleEntityInputResolvesEachHandle(): void {
    [$handle1, $handle2] = $this->storeNodes('Multi 1', 'Multi 2');

    $tool = $this->executeComprehensiveTool(['multiple_entities' => [$handle1, $handle2]]);

    $entities = $tool->getResult()->getContextValues()['result_multiple'];
    $this->assertCount(2, $entities);
    $this->assertContainsOnlyInstancesOf(Node::class, $entities);
    $this->assertSame(['Multi 1', 'Multi 2'], array_map(fn(Node $node) => $node->getTitle(), $entities));

    $formatted = $tool->getFormattedResult();
    $this->assertSame([$handle1, $handle2], $formatted->getContextValues()['result_multiple']);
    $hint_text = implode("\n", $formatted->getHints());
    $this->assertStringContainsString('Output `result_multiple` contains 2 entity handle(s):', $hint_text);
    $this->assertSame(1, substr_count($hint_text, 'Pass these handles to subsequent tool calls'));
  }

  /**
   * Tests a bare handle string for a multiple entity input.
   *
   * The subscriber resolves the handle at priority 100 and, because it does
   * not stop propagation, InputTypeCoercionSubscriber at 50 still wraps the
   * resolved entity into a list.
   */
  public function testBareHandleForMultipleInputIsResolvedThenWrapped(): void {
    [$handle] = $this->storeNodes('Bare');

    $tool = $this->executeComprehensiveTool(['multiple_entities' => $handle]);

    $entities = $tool->getResult()->getContextValues()['result_multiple'];
    $this->assertCount(1, $entities);
    $this->assertInstanceOf(Node::class, $entities[0]);
    $this->assertSame('Bare', $entities[0]->getTitle());
    $this->assertSame([$handle], $tool->getFormattedResult()->getContextValues()['result_multiple']);
  }

  /**
   * Tests a JSON-encoded list of handles for a multiple entity input.
   *
   * The string does not carry the handle prefix, so the subscriber passes it
   * through; InputTypeCoercionSubscriber decodes it and the recursive
   * subscriber resolves each decoded item.
   */
  public function testJsonEncodedHandleListIsDecodedThenResolved(): void {
    [$handle1, $handle2] = $this->storeNodes('JSON 1', 'JSON 2');

    $tool = $this->executeComprehensiveTool(['multiple_entities' => json_encode([$handle1, $handle2])]);

    $entities = $tool->getResult()->getContextValues()['result_multiple'];
    $this->assertCount(2, $entities);
    $this->assertContainsOnlyInstancesOf(Node::class, $entities);
    $this->assertSame([$handle1, $handle2], $tool->getFormattedResult()->getContextValues()['result_multiple']);
  }

  /**
   * Tests a list input definition whose items are entities.
   *
   * The list's own data type is "list", so the subscriber never matches the
   * outer definition; the recursive subscriber dispatches each item with the
   * entity item definition.
   */
  public function testListDefinitionItemsAreResolved(): void {
    [$handle1, $handle2] = $this->storeNodes('List 1', 'List 2');

    $tool = $this->toolManager->createInstance(
      'entity_list_input_output_tool',
      [],
      new Invoker('tool_test', InvokerCapability::EntitiesAsHandles),
    );
    $tool->setInputValue('entities', [$handle1, $handle2]);
    $tool->execute();
    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());

    $processed = $tool->getResult()->getContextValues()['processed'];
    $this->assertCount(2, $processed);
    $this->assertSame(['Processed: List 1', 'Processed: List 2'], array_map(fn(Node $node) => $node->getTitle(), $processed));

    $formatted = $tool->getFormattedResult();
    $this->assertSame([$handle1, $handle2], $formatted->getContextValues()['processed']);
    $hint_text = implode("\n", $formatted->getHints());
    $this->assertStringContainsString('Output `processed` contains 2 entity handle(s):', $hint_text);
    $this->assertSame(1, substr_count($hint_text, 'Pass these handles to subsequent tool calls'));
    $this->assertSame('Processed: List 1', $this->handleStore->resolve($handle1)->getTitle());
  }

  /**
   * Creates article nodes and stores a handle for each.
   *
   * @return string[]
   *   The handles, in the order of the given titles.
   */
  private function storeNodes(string ...$titles): array {
    $handles = [];
    foreach ($titles as $title) {
      $node = Node::create([
        'type' => 'article',
        'title' => $title,
      ]);
      $node->save();
      $handles[] = $this->handleStore->store($node);
    }
    return $handles;
  }

  /**
   * Executes comprehensive_types_tool with the test invoker and given inputs.
   *
   * The tool's required inputs (name, count, and the single entity) are
   * filled in so each test only states the list-shaped input it covers.
   */
  private function executeComprehensiveTool(array $inputs): ToolInterface {
    [$entity_handle] = $this->storeNodes('Required entity');
    $tool = $this->toolManager->createInstance(
      'comprehensive_types_tool',
      [],
      new Invoker('tool_test', InvokerCapability::EntitiesAsHandles),
    );
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 1);
    $tool->setInputValue('entity', $entity_handle);
    foreach ($inputs as $name => $value) {
      $tool->setInputValue($name, $value);
    }
    $tool->execute();
    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());
    return $tool;
  }

}
