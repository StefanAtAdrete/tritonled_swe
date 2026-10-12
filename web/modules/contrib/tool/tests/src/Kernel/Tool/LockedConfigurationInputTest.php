<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Exception\InputException;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\InputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the locked-input and configuration half of TypedInputsTrait.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedInputsTrait
 */
#[RunTestsInSeparateProcesses]
class LockedConfigurationInputTest extends KernelTestBase {

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
   * Tests that setting a locked input throws an InputException.
   *
   * @covers ::setInputValue
   */
  public function testSetLockedInputThrows(): void {
    $tool = $this->toolManager->createInstance('locked_input_tool');
    $this->expectException(InputException::class);
    $this->expectExceptionMessage('The api_mode input is locked and cannot be changed.');
    $tool->setInputValue('api_mode', 'override-attempt');
  }

  /**
   * Tests that a locked input always resolves to its default value.
   *
   * Even an explicitly set configuration value must not override a locked
   * input during execution — locked wins the whole resolution chain.
   *
   * @covers ::getExecutableValue
   */
  public function testLockedValueWinsResolutionChain(): void {
    $tool = $this->toolManager->createInstance('locked_input_tool');
    $tool->setConfigurationValue('api_mode', 'from-config');
    $tool->setInputValue('name', 'from-input');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $tool->getResultMessage());
    $this->assertSame('locked-default', $result->getContextValues()['api_mode']);
    $this->assertSame('from-input', $result->getContextValues()['name']);
  }

  /**
   * Tests that an invalid configuration value throws on set.
   *
   * @covers ::setConfigurationValue
   */
  public function testSetConfigurationValueValidates(): void {
    $tool = $this->toolManager->createInstance('coercion_test_tool');
    $this->expectException(InputException::class);
    // The 'category' input carries a Choice constraint.
    $tool->setConfigurationValue('category', 'not-a-valid-choice');
  }

  /**
   * Tests that skip_validation bypasses constraint checking on set.
   *
   * @covers ::setConfigurationValue
   * @covers ::getConfigurationValue
   */
  public function testSetConfigurationValueSkipValidation(): void {
    $tool = $this->toolManager->createInstance('coercion_test_tool');
    $tool->setConfigurationValue('category', 'not-a-valid-choice', TRUE);
    $this->assertSame('not-a-valid-choice', $tool->getConfigurationValue('category'));
  }

  /**
   * Tests the defaultConfiguration() shape.
   *
   * One entry per configurable input definition, each holding the
   * definition's default value (NULL when none is declared). Locked inputs
   * are excluded: they cannot be configured, so they have no place in the
   * configuration surface.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfigurationShape(): void {
    $tool = $this->toolManager->createInstance('locked_input_tool');
    $defaults = $tool->defaultConfiguration();

    $this->assertSame(['name'], array_keys($defaults));
    $this->assertNull($defaults['name']);
  }

  /**
   * Tests that set values survive a definitions swap.
   *
   * SetInputDefinitions() migrates existing input values onto the replaced
   * definitions so a swap mid-lifecycle does not drop caller-provided state.
   *
   * @covers ::setInputDefinitions
   */
  public function testSetInputDefinitionsMigratesValues(): void {
    $tool = $this->toolManager->createInstance('locked_input_tool');
    $tool->setInputValue('name', 'keep-me');

    $definitions = $tool->getInputDefinitions(TRUE);
    $definitions['name'] = new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Renamed label'),
      description: new TranslatableMarkup('A swapped definition.'),
      required: FALSE,
    );
    $tool->setInputDefinitions($definitions);

    $this->assertSame('keep-me', $tool->getInputValue('name'));
    $this->assertSame('Renamed label', (string) $tool->getInputDefinition('name')->getLabel());
  }

  /**
   * Tests that an unknown input name throws from getInputDefinition().
   *
   * @covers ::getInputDefinition
   */
  public function testUnknownInputDefinitionThrows(): void {
    $tool = $this->toolManager->createInstance('locked_input_tool');
    $this->expectException(InputException::class);
    $this->expectExceptionMessage('The nonexistent context is not a valid context.');
    $tool->getInputDefinition('nonexistent');
  }

}
