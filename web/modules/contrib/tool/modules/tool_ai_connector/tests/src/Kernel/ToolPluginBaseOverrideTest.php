<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ai\Service\FunctionCalling\OverridableFunctionCallInterface;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool_ai_connector\Context\ToolContextDefinition;
use Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface;
use Drupal\tool_ai_connector\Plugin\AiFunctionCall\ToolPluginBase;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests per-instance context definition overrides on ToolPluginBase.
 *
 * @group tool_ai_connector
 *
 * @covers \Drupal\tool_ai_connector\Plugin\AiFunctionCall\ToolPluginBase::getContextDefinition
 * @covers \Drupal\tool_ai_connector\Plugin\AiFunctionCall\ToolPluginBase::getContextDefinitions
 */
#[RunTestsInSeparateProcesses]
final class ToolPluginBaseOverrideTest extends KernelTestBase {

  /**
   * The deprecation that declaring the multiple input these tests use raises.
   */
  protected const MULTIPLE_DEPRECATION = "Declaring the 'tags' input with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049";

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->functionCallManager = $this->container->get('plugin.manager.ai.function_calls');
  }

  /**
   * Tests that ToolPluginBase implements OverridableFunctionCallInterface.
   */
  public function testImplementsOverridableInterface(): void {
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');
    $this->assertInstanceOf(OverridableFunctionCallInterface::class, $instance);
  }

  /**
   * Tests that getContextDefinitions() includes the tool's input definitions.
   */
  public function testGetContextDefinitionsContainsInputDefinitions(): void {
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');
    $definitions = $instance->getContextDefinitions();

    $this->assertArrayHasKey('name', $definitions);
    $this->assertSame('string', $definitions['name']->getDataType());
    $this->assertArrayHasKey('greeting', $definitions);
  }

  /**
   * Tests that an override takes precedence in getContextDefinition().
   */
  public function testGetContextDefinitionOverride(): void {
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');

    $original = $instance->getContextDefinition('name');
    $this->assertSame('string', $original->getDataType());

    $override = new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Overridden Name'),
      required: FALSE,
    );
    $greetingOriginal = $instance->getContextDefinition('greeting');
    $instance->setContextDefinitionOverride('name', $override);

    $after = $instance->getContextDefinition('name');
    $this->assertSame($override, $after);
    $this->assertSame('integer', $after->getDataType());

    // Overriding 'name' must not affect the 'greeting' definition.
    $this->assertSame($greetingOriginal, $instance->getContextDefinition('greeting'));
  }

  /**
   * Tests that an override takes precedence in getContextDefinitions().
   */
  public function testGetContextDefinitionsOverride(): void {
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');

    $override = new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Overridden Name'),
      required: FALSE,
    );
    $greetingOriginal = $instance->getContextDefinitions()['greeting'];
    $instance->setContextDefinitionOverride('name', $override);

    $definitions = $instance->getContextDefinitions();
    $this->assertSame($override, $definitions['name']);

    // Overriding 'name' must leave the 'greeting' definition unchanged.
    $this->assertArrayHasKey('greeting', $definitions);
    $this->assertSame($greetingOriginal, $definitions['greeting']);
  }

  /**
   * Tests that overrides are scoped to a single plugin instance.
   */
  public function testOverrideIsPerInstance(): void {
    $instance_a = $this->functionCallManager->createInstance('tool:greeting_tool');
    $instance_b = $this->functionCallManager->createInstance('tool:greeting_tool');

    $override = new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Only On A'),
      required: FALSE,
    );
    $instance_a->setContextDefinitionOverride('name', $override);

    $this->assertSame('integer', $instance_a->getContextDefinition('name')->getDataType());
    $this->assertSame('string', $instance_b->getContextDefinition('name')->getDataType());
  }

  /**
   * Tests schema overrides use Tool API's invoker-aware normalizer.
   */
  public function testInputSchemaOverrideUsesToolDefinitionSerializer(): void {
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');
    $this->assertInstanceOf(ToolPluginBase::class, $instance);

    // Overrides cross the ai module boundary typed on core context
    // definitions; a tool-shaped override travels as an adapter, whose
    // wrapped definition the schema build recovers (#3583018).
    $instance->setContextDefinitionOverride('name', ToolContextDefinition::fromToolDefinition(new MapInputDefinition(
      label: new TranslatableMarkup('Override payload'),
      description: new TranslatableMarkup('A tool-style override payload.'),
      property_definitions: [
        'featured_entity' => new EntityInputDefinition(
          data_type: 'node',
          label: new TranslatableMarkup('Featured entity'),
          description: new TranslatableMarkup('A nested entity override.'),
        ),
      ],
    )));

    $schema = $instance->getInputSchema();
    $featured_entity_schema = $schema['properties']['name']['properties']['featured_entity'];
    $this->assertSame('string', $featured_entity_schema['type']);
    $this->assertStringContainsString('pass a handle', $featured_entity_schema['description']);

    $rendered = $instance->normalize()->renderFunctionArray();
    $rendered_featured_entity_schema = $rendered['parameters']['properties']['name']['properties']['featured_entity'];
    $this->assertSame('string', $rendered_featured_entity_schema['type']);
    $this->assertStringContainsString('pass a handle', $rendered_featured_entity_schema['description']);
  }

  /**
   * Tests a converted multiple input crosses as core's multiple.
   *
   * The ai module reads the list a multiple input converts to (#3583049) as
   * the multiple definition it was: the item's data type, multiple TRUE.
   */
  #[IgnoreDeprecations]
  public function testConvertedMultipleInputAdaptsAsCoreMultiple(): void {
    $this->expectDeprecation(self::MULTIPLE_DEPRECATION);
    $this->declareMultipleInput();
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');

    $definition = $instance->getContextDefinition('tags');
    $this->assertInstanceOf(ContextDefinitionInterface::class, $definition);
    $this->assertInstanceOf(ToolContextDefinitionInterface::class, $definition);
    $this->assertSame('string', $definition->getDataType());
    $this->assertTrue($definition->isMultiple());
    $this->assertFalse($definition->isRequired());
  }

  /**
   * Tests an only_allow override advertises the allowed item values.
   *
   * The only_allow restriction of ai_agents adds Choice to a clone of the
   * adapter. On a multiple definition Choice bound each item and reached the
   * schema as items.enum, and so it must on a converted multiple input.
   */
  #[IgnoreDeprecations]
  public function testOnlyAllowOverrideAdvertisesItemEnum(): void {
    $this->expectDeprecation(self::MULTIPLE_DEPRECATION);
    $this->declareMultipleInput();
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');
    $this->assertInstanceOf(ToolPluginBase::class, $instance);

    $override = clone $instance->getContextDefinition('tags');
    $override->addConstraint('Choice', ['news', 'sports']);
    $instance->setContextDefinitionOverride('tags', $override);

    $schema = $instance->getInputSchema();
    $this->assertSame(['array', 'null'], $schema['properties']['tags']['type']);
    $this->assertSame(['news', 'sports'], $schema['properties']['tags']['items']['enum']);
    $this->assertArrayNotHasKey('enum', $schema['properties']['tags']);
  }

  /**
   * Tests execute() mirrors a converted multiple input into its context.
   */
  #[IgnoreDeprecations]
  public function testExecuteMirrorsConvertedMultipleInput(): void {
    $this->expectDeprecation(self::MULTIPLE_DEPRECATION);
    $this->declareMultipleInput();
    $instance = $this->functionCallManager->createInstance('tool:greeting_tool');
    $this->assertInstanceOf(ToolPluginBase::class, $instance);
    $instance->setContextValue('name', 'Ada');
    $instance->setContextValue('tags', ['news', 'sports']);
    $instance->execute();

    $this->assertTrue($instance->getStructuredOutput()['success']);
    $this->assertSame(['news', 'sports'], $instance->getContextValue('tags'));
    $context_definition = $instance->getContext('tags')->getContextDefinition();
    $this->assertSame('string', $context_definition->getDataType());
    $this->assertTrue($context_definition->isMultiple());
  }

  /**
   * Declares a multiple input on the greeting tool.
   *
   * The tool definition converts it to a list, with the deprecation in
   * self::MULTIPLE_DEPRECATION (#3583049). Declared before any function call
   * is created, so the derived function call definitions carry it as well.
   */
  protected function declareMultipleInput(): void {
    $this->container->get('plugin.manager.tool')->getDefinition('greeting_tool')->addInputDefinition('tags', new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('Some tags.'),
      required: FALSE,
      multiple: TRUE,
    ));
  }

}
