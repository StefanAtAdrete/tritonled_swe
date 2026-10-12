<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Handle\ToolHandleStore;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that entity handle write-back happens at execute time.
 *
 * When an LLM emits multiple tool calls in one response (e.g. two
 * field-setting calls against the same entity handle), each call must see
 * the mutations of the calls executed before it. That requires the handle
 * store to be updated when a tool executes — not later, as a side effect of
 * rendering the tool's output. This test executes a mutating tool through
 * the AI connector and asserts the store holds the mutated entity without
 * any output getter having been called.
 *
 * @group tool_ai_connector
 */
#[Group('tool_ai_connector')]
#[RunTestsInSeparateProcesses]
final class ExecuteTimeHandleWriteBackTest extends KernelTestBase {

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
   * The function call plugin manager.
   *
   * @var \Drupal\ai\Service\FunctionCalling\FunctionCallPluginManager
   */
  protected $functionCallManager;

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

    $this->functionCallManager = $this->container->get('plugin.manager.ai.function_calls');
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Tests the handle store is updated by execute() alone.
   */
  public function testHandleStoreUpdatedAtExecuteTime(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Original',
    ]);
    $node->save();
    $handle = $this->handleStore->store($node);

    // Execute a tool that mutates the entity's title and returns the entity
    // as an output. Crucially, do NOT call getStructuredOutput() or
    // getReadableOutput() — the write-back must not depend on rendering.
    $function_call = $this->functionCallManager->createInstance('tool:comprehensive_types_tool');
    $function_call->setContextValue('name', 'test');
    $function_call->setContextValue('count', 1);
    $function_call->setContextValue('entity', $handle);
    $function_call->execute();

    $stored = $this->handleStore->resolve($handle);
    $this->assertSame('Output: Original', $stored->label(),
      'The mutated entity was written back to the same handle at execute time.');
  }

  /**
   * Tests a second call on the same handle sees the first call's mutation.
   *
   * Models the entity_load → field_set_value → field_set_value batch: both
   * setter calls reference the handle minted by entity_load, and the second
   * must act on the entity as mutated by the first.
   */
  public function testSequentialCallsOnSameHandleAccumulateMutations(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Original',
    ]);
    $node->save();
    $handle = $this->handleStore->store($node);

    $first = $this->functionCallManager->createInstance('tool:comprehensive_types_tool');
    $first->setContextValue('name', 'first');
    $first->setContextValue('count', 1);
    $first->setContextValue('entity', $handle);
    $first->execute();

    // Second call, same handle, no output rendering in between — as when an
    // LLM emits both calls in a single response and the runner executes the
    // batch before collecting outputs.
    $second = $this->functionCallManager->createInstance('tool:comprehensive_types_tool');
    $second->setContextValue('name', 'second');
    $second->setContextValue('count', 2);
    $second->setContextValue('entity', $handle);
    $second->execute();

    $stored = $this->handleStore->resolve($handle);
    $this->assertSame('Output: Output: Original', $stored->label(),
      'The second call received the entity as mutated by the first call.');
  }

}
