<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests invoker-aware input/output transformations for tools.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\Tool\ToolBase
 */
#[RunTestsInSeparateProcesses]
class ToolInvokerTest extends KernelTestBase {

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
   * Tests that setInvoker() and getInvoker() work correctly.
   *
   * @covers ::setInvoker
   * @covers ::getInvoker
   * @covers ::hasInvoker
   */
  public function testInvokerGetterSetter(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');

    // No invoker set by default.
    $this->assertNull($tool->getInvoker()?->id);
    $this->assertFalse($tool->hasInvoker());

    // Set invoker.
    $result = $tool->setInvoker('ai_agents');
    $this->assertSame($tool, $result, 'setInvoker() returns $this for chaining.');
    $this->assertEquals('ai_agents', $tool->getInvoker()?->id);
    $this->assertTrue($tool->hasInvoker());
  }

  /**
   * Tests that createInstance() with invoker argument sets it on the tool.
   *
   * @covers \Drupal\tool\Tool\ToolManager::createInstance
   */
  public function testCreateInstanceWithInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], 'mcp_server');

    $this->assertEquals('mcp_server', $tool->getInvoker()?->id);
    $this->assertTrue($tool->hasInvoker());
  }

  /**
   * Tests that createInstance() without invoker leaves it NULL.
   *
   * @covers \Drupal\tool\Tool\ToolManager::createInstance
   */
  public function testCreateInstanceWithoutInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');

    $this->assertNull($tool->getInvoker()?->id);
    $this->assertFalse($tool->hasInvoker());
  }

  /**
   * Tests that ToolInputTransformEvent is dispatched on setInputValue().
   *
   * @covers ::setInputValue
   */
  public function testInputTransformEventDispatched(): void {
    $dispatched = [];

    // Register a subscriber that records events.
    $this->container->get('event_dispatcher')->addListener(
      ToolInputTransformEvent::class,
      function (ToolInputTransformEvent $event) use (&$dispatched) {
        $dispatched[] = [
          'name' => $event->getInputName(),
          'value' => $event->getValue(),
          'invoker' => $event->getInvoker()?->id,
          'tool_id' => $event->getTool()->getPluginId(),
        ];
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
    $tool->setInputValue('name', 'World');

    $this->assertCount(1, $dispatched);
    $this->assertEquals('name', $dispatched[0]['name']);
    $this->assertEquals('World', $dispatched[0]['value']);
    $this->assertEquals('ai_agents', $dispatched[0]['invoker']);
    $this->assertEquals('greeting_tool', $dispatched[0]['tool_id']);
  }

  /**
   * Tests that ToolInputTransformEvent allows value transformation.
   *
   * @covers ::setInputValue
   */
  public function testInputTransformEventTransformsValue(): void {
    // Register a subscriber that transforms the value.
    $this->container->get('event_dispatcher')->addListener(
      ToolInputTransformEvent::class,
      function (ToolInputTransformEvent $event) {
        if ($event->getInvoker()?->id === 'ai_agents' && $event->getInputName() === 'name') {
          // Simulate upcasting: transform a token to a resolved value.
          $event->setValue('Transformed ' . $event->getValue());
        }
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess());
    // The greeting should use the transformed value.
    $this->assertEquals('Hello, Transformed World!', $result->getContextValues()['message']);
  }

  /**
   * Tests that input transform does not fire without invoker.
   *
   * The event still dispatches but invoker is NULL, so conditional
   * subscribers can skip transformation.
   *
   * @covers ::setInputValue
   */
  public function testInputTransformEventWithoutInvoker(): void {
    $dispatched = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputTransformEvent::class,
      function (ToolInputTransformEvent $event) use (&$dispatched) {
        $dispatched[] = $event->getInvoker()?->id;
        // Only transform if invoker is set.
        if ($event->getInvoker()?->id === 'ai_agents') {
          $event->setValue('SHOULD NOT HAPPEN');
        }
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $this->assertCount(1, $dispatched);
    $this->assertNull($dispatched[0]);
    // Value should remain untransformed.
    $this->assertEquals('Hello, World!', $tool->getResult()->getContextValues()['message']);
  }

  /**
   * Tests that getResult() returns raw untransformed result.
   *
   * @covers ::getResult
   */
  public function testGetResultReturnsRawResult(): void {
    // Register a subscriber that would transform output.
    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) {
        $event->setValue('TRANSFORMED');
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    // getResult() should return the raw, untransformed result.
    $result = $tool->getResult();
    $this->assertEquals('Hello, World!', $result->getContextValues()['message']);
  }

  /**
   * Tests that getFormattedResult() dispatches per-output transform events.
   *
   * @covers ::getFormattedResult
   */
  public function testFormattedResultPerOutputTransform(): void {
    $dispatched = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) use (&$dispatched) {
        $dispatched[] = [
          'name' => $event->getOutputName(),
          'value' => $event->getValue(),
          'invoker' => $event->getInvoker()?->id,
        ];
        if ($event->getInvoker()?->id === 'ai_agents') {
          $event->setValue('[token:' . $event->getValue() . ']');
        }
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $formatted = $tool->getFormattedResult();

    $this->assertCount(1, $dispatched);
    $this->assertEquals('message', $dispatched[0]['name']);
    $this->assertEquals('Hello, World!', $dispatched[0]['value']);
    $this->assertEquals('ai_agents', $dispatched[0]['invoker']);

    // Formatted result should have the transformed value.
    $this->assertEquals('[token:Hello, World!]', $formatted->getContextValues()['message']);
  }

  /**
   * Tests getFormattedResult() without invoker still works.
   *
   * @covers ::getFormattedResult
   */
  public function testFormattedResultWithoutInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    // Without subscribers that transform, formatted result equals raw result.
    $formatted = $tool->getFormattedResult();
    $raw = $tool->getResult();

    $this->assertEquals($raw->getMessage(), $formatted->getMessage());
    $this->assertEquals($raw->getContextValues(), $formatted->getContextValues());
  }

  /**
   * Tests getFormattedResult() throws before execute.
   *
   * @covers ::getFormattedResult
   */
  public function testFormattedResultBeforeExecuteThrows(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');

    $this->expectException(\BadMethodCallException::class);
    $tool->getFormattedResult();
  }

  /**
   * Tests multiple output values are each transformed individually.
   *
   * @covers ::getFormattedResult
   */
  public function testMultipleOutputValuesTransformed(): void {
    $transformed = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) use (&$transformed) {
        $transformed[] = $event->getOutputName();
        $event->setValue($event->getValue() * 10);
      }
    );

    $tool = $this->toolManager->createInstance('numeric_input_tool', [], 'ai_agents');
    $tool->setInputValue('number', 5);
    $tool->execute();

    $formatted = $tool->getFormattedResult();

    $this->assertContains('doubled', $transformed);
    // Original doubled value is 10, transformed should be 100.
    $this->assertEquals(100, $formatted->getContextValues()['doubled']);
  }

  /**
   * Tests that messages added during output transform are appended to result.
   *
   * @covers ::getFormattedResult
   */
  public function testOutputTransformMessagesAggregated(): void {
    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) {
        if ($event->getOutputName() === 'message') {
          $event->addMessage('First annotation.');
          $event->addMessage('Second annotation.');
        }
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $formatted = $tool->getFormattedResult();

    // Original message stays untouched.
    $this->assertEquals('Greeting generated.', (string) $formatted->getMessage());
    // Annotations are kept separate.
    $hints = $formatted->getHints();
    $this->assertCount(2, $hints);
    $this->assertContains('First annotation.', $hints);
    $this->assertContains('Second annotation.', $hints);
  }

  /**
   * Tests that no messages results in original message unchanged.
   *
   * @covers ::getFormattedResult
   */
  public function testNoMessagesKeepsOriginalMessage(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $formatted = $tool->getFormattedResult();
    // No subscribers adding messages, so message should match the raw result.
    $this->assertEquals((string) $tool->getResult()->getMessage(), (string) $formatted->getMessage());
    // No hints.
    $this->assertEmpty($formatted->getHints());
  }

}
