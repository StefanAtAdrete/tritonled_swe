<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Handle\ToolHandleStore;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests a failure result is seeded when the tool never executes.
 *
 * The ai_agents image collection loop calls getPluginInstance()->getResult()
 * without checking hasResult(). When a tool's input stage fails, the tool
 * never executes and getResult() would throw BadMethodCallException,
 * aborting the agent run and masking the connector's error feedback. The
 * connector therefore seeds a failure result so direct result reads are
 * safe.
 *
 * @group tool_ai_connector
 */
#[Group('tool_ai_connector')]
#[RunTestsInSeparateProcesses]
final class SeededFailureResultTest extends KernelTestBase {

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
  }

  /**
   * Tests getResult() is safe after an input-stage failure.
   */
  public function testFailureResultSeededWhenInputStageFails(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Seed test',
    ]);
    $node->save();
    $handle_store = $this->container->get('tool.handle_store');
    $this->assertInstanceOf(ToolHandleStore::class, $handle_store);
    $handle = $handle_store->store($node);

    $function_call = $this->container->get('plugin.manager.ai.function_calls')
      ->createInstance('tool:comprehensive_types_tool');
    $function_call->setContextValue('name', 'test');
    $function_call->setContextValue('count', 1);
    $function_call->setContextValue('entity', $handle);
    // A scalar for a map-typed input throws during setInputValue(), so the
    // wrapped tool never executes.
    $function_call->setContextValue('metadata', 'not-a-map');
    $function_call->execute();

    $tool = $function_call->getPluginInstance();
    $this->assertTrue($tool->hasResult(), 'A result was seeded despite the tool not executing.');
    $this->assertFalse($tool->hasExecuted(), 'The seeded result does not masquerade as an execution.');
    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Tool plugin execution failed', (string) $result->getMessage());

    // The connector's own error feedback is unaffected.
    $structured = $function_call->getStructuredOutput();
    $this->assertFalse($structured['success']);
    $this->assertStringContainsString('Tool plugin execution failed', $structured['message']);
    $this->assertArrayHasKey('input_schema', $structured);
  }

  /**
   * Tests setFailureResult() refuses to overwrite an existing result.
   */
  public function testSetFailureResultCannotOverwriteExistingResult(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Overwrite test',
    ]);
    $node->save();
    $handle = $this->container->get('tool.handle_store')->store($node);

    $function_call = $this->container->get('plugin.manager.ai.function_calls')
      ->createInstance('tool:comprehensive_types_tool');
    $function_call->setContextValue('name', 'test');
    $function_call->setContextValue('count', 1);
    $function_call->setContextValue('entity', $handle);
    $function_call->execute();

    $tool = $function_call->getPluginInstance();
    $this->assertTrue($tool->hasExecuted());
    $this->assertTrue($tool->getResult()->isSuccess());

    $this->expectException(\LogicException::class);
    $tool->setFailureResult(new TranslatableMarkup('Should not overwrite.'));
  }

}
