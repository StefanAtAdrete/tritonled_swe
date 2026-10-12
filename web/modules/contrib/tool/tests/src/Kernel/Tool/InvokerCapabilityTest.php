<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool_test\TestInvokerCapability;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that subscribers can gate on capabilities from any module's enum.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
final class InvokerCapabilityTest extends KernelTestBase {

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
   */
  private ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests a capability defined outside Tool API is honored.
   */
  public function testModuleDefinedCapabilityIsHonored(): void {
    $this->assertSame(
      'HELLO, ADA!',
      $this->greet(new Invoker('tool_test', TestInvokerCapability::UppercaseOutputs)),
    );
  }

  /**
   * Tests an invoker without the capability is left alone.
   */
  public function testInvokerWithoutCapabilityIsUntouched(): void {
    $this->assertSame('Hello, Ada!', $this->greet(new Invoker('tool_test')));
    $this->assertSame('Hello, Ada!', $this->greet('tool_test'));
  }

  /**
   * Tests a case from a different enum does not satisfy the check.
   */
  public function testCapabilityFromAnotherEnumDoesNotMatch(): void {
    $this->assertSame(
      'Hello, Ada!',
      $this->greet(new Invoker('tool_test', InvokerCapability::EntitiesAsHandles)),
    );
  }

  /**
   * Tests capabilities from several enums coexist on one invoker.
   */
  public function testCapabilitiesFromSeveralEnumsCoexist(): void {
    $invoker = new Invoker(
      'tool_test',
      InvokerCapability::EntitiesAsHandles,
      TestInvokerCapability::UppercaseOutputs,
    );
    $this->assertTrue($invoker->has(InvokerCapability::EntitiesAsHandles));
    $this->assertTrue($invoker->has(TestInvokerCapability::UppercaseOutputs));
    $this->assertSame('HELLO, ADA!', $this->greet($invoker));
  }

  /**
   * Runs greeting_tool for an invoker and returns its transformed message.
   */
  private function greet(string|Invoker $invoker): string {
    $tool = $this->toolManager->createInstance('greeting_tool', [], $invoker);
    $tool->setInputValue('name', 'Ada');
    $tool->execute();
    $this->assertTrue($tool->getResult()->isSuccess());
    return $tool->getFormattedResult()->getContextValues()['message'];
  }

}
