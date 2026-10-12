<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolManager;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the untested remainder of TypedOutputsTrait.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedOutputsTrait
 */
#[RunTestsInSeparateProcesses]
class TypedOutputsTest extends KernelTestBase {

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
  protected ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests validateOutputs() passes for a valid set output.
   *
   * @covers ::validateOutputs
   * @covers ::setOutputValue
   */
  public function testValidateOutputsCleanPass(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setOutputValue('message', 'A valid string message.');

    $this->assertCount(0, $tool->validateOutputs());
  }

  /**
   * Tests validateOutputs() reports a violation for an invalid output.
   *
   * A structurally wrong value for a string output must produce a violation
   * rather than passing silently or throwing.
   *
   * @covers ::validateOutputs
   * @covers ::setOutputValue
   */
  public function testValidateOutputsReportsViolation(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setOutputValue('message', ['not' => 'a string']);

    $this->assertGreaterThan(0, $tool->validateOutputs()->count());
  }

  /**
   * Tests setOutputValue() against an undeclared output name throws.
   *
   * @covers ::setOutputValue
   * @covers ::getOutputDefinition
   */
  public function testSetOutputValueUndeclaredNameThrows(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $this->expectException(ContextException::class);
    $tool->setOutputValue('nonexistent', 'value');
  }

}
