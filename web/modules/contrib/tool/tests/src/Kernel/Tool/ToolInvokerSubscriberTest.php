<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool_test\EventSubscriber\TestToolTransformSubscriber;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests invoker-aware transformations with a real event subscriber.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolInvokerSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests that the subscriber transforms input when invoker matches.
   *
   * The TestToolTransformSubscriber resolves email addresses to usernames
   * for the 'name' input when the invoker is 'test_invoker'.
   */
  public function testSubscriberTransformsInput(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);
    $tool->setInputValue('name', 'john@example.com');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess());
    // The subscriber should have resolved 'john@example.com' to 'john'.
    $this->assertEquals('Hello, john!', $result->getContextValues()['message']);
  }

  /**
   * Tests that the subscriber does NOT transform when invoker does not match.
   */
  public function testSubscriberDoesNotTransformForOtherInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], 'some_other_invoker');
    $tool->setInputValue('name', 'john@example.com');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess());
    // Value should NOT be transformed — full email used as-is.
    $this->assertEquals('Hello, john@example.com!', $result->getContextValues()['message']);
  }

  /**
   * Tests that the subscriber does NOT transform when no invoker is set.
   */
  public function testSubscriberDoesNotTransformWithoutInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setInputValue('name', 'john@example.com');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess());
    // Value should NOT be transformed.
    $this->assertEquals('Hello, john@example.com!', $result->getContextValues()['message']);
  }

  /**
   * Tests that getResult() returns raw result unaffected by output subscriber.
   */
  public function testRawResultUnaffectedByOutputSubscriber(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);
    $tool->setInputValue('name', 'World');
    $tool->execute();

    // getResult() should return the raw result — no output transformation.
    $result = $tool->getResult();
    $this->assertEquals('Hello, World!', $result->getContextValues()['message']);
  }

  /**
   * Tests that getFormattedResult() applies per-output-value transformation.
   *
   * The subscriber wraps the 'message' output in a [token:...] format.
   */
  public function testFormattedResultAppliesOutputTransform(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    // After per-output transform, message value gets wrapped in [token:...].
    $this->assertTrue($formatted->isSuccess());
    $this->assertArrayHasKey('message', $formatted->getContextValues());
    $this->assertEquals('[token:Hello, World!]', $formatted->getContextValues()['message']);
  }

  /**
   * Tests the full pipeline: input transform → execute → output transform.
   *
   * Verifies the complete flow with a real subscriber:
   * 1. Input: email → username (upcast)
   * 2. Execute: tool uses the username
   * 3. Output: message → [token:message] (downcast per-value)
   */
  public function testFullPipeline(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);
    $tool->setInputValue('name', 'alice@example.com');
    $tool->execute();

    // Raw result used the transformed input (alice, not alice@example.com).
    $raw = $tool->getResult();
    $this->assertEquals('Hello, alice!', $raw->getContextValues()['message']);

    // Formatted result applies output transforms.
    $formatted = $tool->getFormattedResult();
    $this->assertEquals('[token:Hello, alice!]', $formatted->getContextValues()['message']);
  }

  /**
   * Tests that setInvoker() after creation works the same as createInstance().
   */
  public function testSetInvokerAfterCreation(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setInvoker(TestToolTransformSubscriber::INVOKER_ID);
    $tool->setInputValue('name', 'bob@example.com');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertEquals('Hello, bob!', $result->getContextValues()['message']);
  }

  /**
   * Tests that output transform hints are on the formatted result.
   */
  public function testFormattedResultIncludesTransformMessages(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);
    $tool->setInputValue('name', 'World');
    $tool->execute();

    // The raw result message should be unchanged.
    $raw = $tool->getResult();
    $this->assertEquals('Greeting generated.', (string) $raw->getMessage());

    // The formatted result message should also be unchanged (no result
    // transform to alter it).
    $formatted = $tool->getFormattedResult();
    $this->assertEquals('Greeting generated.', (string) $formatted->getMessage());

    // Hints from the output transform subscriber should be present.
    $hints = $formatted->getHints();
    $this->assertNotEmpty($hints);
    $this->assertStringContainsString('Use', $hints[0]);
  }

  /**
   * Tests annotation aggregation without the result transform overriding.
   *
   * Uses a tool with a different invoker that only has output transforms
   * (no result transform) to verify annotations are collected.
   */
  public function testMessageAggregationDirectly(): void {
    // Register an inline subscriber that adds a message but doesn't do
    // a full result transform.
    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) {
        if ($event->getInvoker()?->id === 'message_test_invoker') {
          $event->addMessage('Note: this value was transformed.');
          $event->addMessage('Additional context here.');
        }
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'message_test_invoker');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $formatted = $tool->getFormattedResult();

    // Original message stays untouched.
    $this->assertEquals('Greeting generated.', (string) $formatted->getMessage());
    // Annotations are kept separate.
    $hints = $formatted->getHints();
    $this->assertContains('Note: this value was transformed.', $hints);
    $this->assertContains('Additional context here.', $hints);
  }

}
