<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\tool\Handle\ToolHandleStore;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that context data is available after tool execution.
 *
 * Regression test for #3576586: consumers reading context values from a
 * function call after execute() must receive the transformed input values —
 * upcasted entities rather than raw handle strings — instead of hitting a
 * ContextException because the contexts were never populated.
 *
 * @group tool_ai_connector
 */
#[Group('tool_ai_connector')]
#[RunTestsInSeparateProcesses]
final class ContextDataAfterExecuteTest extends KernelTestBase {

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
   * Tests context values are readable and upcasted after execute().
   */
  public function testContextValuesAvailableAfterExecute(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Original',
    ]);
    $node->save();
    $handle = $this->handleStore->store($node);

    $function_call = $this->functionCallManager->createInstance('tool:comprehensive_types_tool');
    $function_call->setContextValue('name', 'test');
    $function_call->setContextValue('count', 7);
    $function_call->setContextValue('entity', $handle);
    $function_call->execute();

    // Scalar contexts are readable after execution.
    $this->assertSame('test', $function_call->getContextValue('name'));
    $this->assertSame(7, $function_call->getContextValue('count'));

    // The entity context holds the upcasted entity, not the handle string.
    $entity = $function_call->getContextValue('entity');
    $this->assertInstanceOf(NodeInterface::class, $entity);
    $this->assertSame($node->id(), $entity->id());
  }

  /**
   * Tests an explicit NULL mirrors as the tool resolves it.
   *
   * The mirrored context must carry the tool's value resolution: an
   * optional multiple input explicitly set to NULL resolves to NULL, not
   * to the empty array a typed-data reconstruction would produce.
   */
  public function testExplicitNullMirrorsAsValueless(): void {
    $node = Node::create(['type' => 'article', 'title' => 'Null mirror']);
    $node->save();
    $handle = $this->handleStore->store($node);

    $function_call = $this->functionCallManager->createInstance('tool:comprehensive_types_tool');
    $function_call->setContextValue('name', 'test');
    $function_call->setContextValue('count', 7);
    $function_call->setContextValue('entity', $handle);
    $function_call->setContextValue('labels', NULL);
    $function_call->execute();

    $this->assertNull($function_call->getContextValue('labels'));
  }

}
