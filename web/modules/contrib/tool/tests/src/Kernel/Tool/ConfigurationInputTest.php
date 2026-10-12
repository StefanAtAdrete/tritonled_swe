<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the configuration source of the input value resolution chain.
 *
 * Regression coverage for the configuration source being broken end-to-end:
 * getConfigurationValue() inverted its $return_typed_data parameter,
 * getExecutableValue() called getValue() on a Context, and
 * getConfiguration() returned Context objects and threw for unset keys.
 *
 * @group tool
 */
#[Group('tool')]
#[RunTestsInSeparateProcesses]
class ConfigurationInputTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'tool',
    'tool_test',
  ];

  /**
   * The tool manager.
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
   * Tests getConfigurationValue() returns what its parameter says.
   */
  public function testGetConfigurationValueSemantics(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setConfigurationValue('number', 21);

    $this->assertSame(21, $tool->getConfigurationValue('number'));
    $this->assertInstanceOf(TypedDataInterface::class, $tool->getConfigurationValue('number', TRUE));
    $this->assertNull($tool->getConfigurationValue('never_set'));
  }

  /**
   * Tests a tool executes with a configuration-sourced value.
   */
  public function testExecutionWithConfigurationSourcedValue(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setConfigurationValue('number', 21);

    $tool->execute();

    $this->assertTrue($tool->getResult()->isSuccess());
    $this->assertSame(42, $tool->getOutputValue('doubled'));
  }

  /**
   * Tests input values take precedence over configuration values.
   */
  public function testInputValuePrecedesConfigurationValue(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setConfigurationValue('number', 21);
    $tool->setInputValue('number', 5);

    $tool->execute();

    $this->assertSame(10, $tool->getOutputValue('doubled'));
  }

  /**
   * Tests getConfiguration() returns a plain array without throwing.
   */
  public function testGetConfigurationReturnsPlainValues(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');

    // Unset keys fall back to the definition default instead of throwing.
    $configuration = $tool->getConfiguration();
    $this->assertArrayHasKey('number', $configuration);
    $this->assertNotInstanceOf(TypedDataInterface::class, $configuration['number']);

    $tool->setConfigurationValue('number', 21);
    $configuration = $tool->getConfiguration();
    $this->assertSame(21, $configuration['number']);

    // The round-trip through setConfiguration() preserves plain values.
    $copy = $this->toolManager->createInstance('numeric_input_tool');
    $copy->setConfiguration($configuration);
    $this->assertSame(21, $copy->getConfigurationValue('number'));
    $this->assertSame($configuration, $copy->getConfiguration());
  }

  /**
   * Tests configuration storage tracks only explicitly set values.
   */
  public function testExplicitConfigurationDistinctFromDefaults(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');

    // setConfiguration() with no keys leaves nothing explicitly configured —
    // defaults are applied on read, not seeded into storage.
    $tool->setConfiguration([]);
    $this->assertFalse($tool->hasConfigurationValue('number'));
    $this->assertNull($tool->getConfigurationValue('number'));
    $this->assertArrayHasKey('number', $tool->getConfiguration());

    // setConfiguration() replaces: previously explicit keys not present in
    // the new array are cleared.
    $tool->setConfigurationValue('number', 21);
    $this->assertTrue($tool->hasConfigurationValue('number'));
    $tool->setConfiguration([]);
    $this->assertFalse($tool->hasConfigurationValue('number'));
  }

  /**
   * Tests configuration passed to createInstance() reaches the input chain.
   */
  public function testCreateInstanceConfigurationApplies(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool', ['number' => 21]);

    $this->assertTrue($tool->hasConfigurationValue('number'));
    $this->assertSame(21, $tool->getConfigurationValue('number'));

    $tool->execute();
    $this->assertSame(42, $tool->getOutputValue('doubled'));
  }

}
