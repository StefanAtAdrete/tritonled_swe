<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\Tool\ToolDefinitionNormalizeDirection;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the InputDefinition factory pipeline.
 *
 * Covers fromConfigSchema() (config-schema mappings, sequences, scalars)
 * and fromDataDefinition() (typed-data maps, lists, scalars).
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedData\InputDefinition
 */
#[RunTestsInSeparateProcesses]
class InputDefinitionFactoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'system',
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Tests a config-schema mapping becomes a MapInputDefinition.
   *
   * @covers ::fromConfigSchema
   */
  public function testFromConfigSchemaMapping(): void {
    $definition = InputDefinition::fromConfigSchema([
      'type' => 'tool_test.settings_fixture',
      'label' => 'Settings',
    ]);

    $this->assertInstanceOf(MapInputDefinition::class, $definition);
    $properties = $definition->getPropertyDefinitions();
    $this->assertSame(['title', 'limit', 'tags'], array_keys($properties));
    $this->assertSame('string', $properties['title']->getDataType());
    $this->assertSame('integer', $properties['limit']->getDataType());
    $this->assertInstanceOf(ListInputDefinition::class, $properties['tags']);
  }

  /**
   * Tests a mapping with numeric keys, which PHP stores as integers.
   *
   * @covers ::fromConfigSchema
   */
  public function testFromConfigSchemaNumericKeys(): void {
    $definition = InputDefinition::fromConfigSchema([
      'type' => 'system.site',
      'label' => 'Site',
    ]);

    $this->assertInstanceOf(MapInputDefinition::class, $definition);
    $page = $definition->getPropertyDefinitions()['page'];
    $this->assertInstanceOf(MapInputDefinition::class, $page);
    $this->assertSame([403, 404, 'front'], array_keys($page->getPropertyDefinitions()));
  }

  /**
   * Tests numeric keys survive the transform and normalize paths.
   *
   * The names reach string-typed parameters under strict_types, so each
   * path must pass them as strings.
   */
  public function testNumericKeysAreUsable(): void {
    $definition = InputDefinition::fromConfigSchema([
      'type' => 'system.site',
      'label' => 'Site',
    ]);
    $tool = $this->container->get('plugin.manager.tool')->createInstance('no_requirements_tool');
    $dispatcher = $this->container->get('event_dispatcher');
    $value = ['page' => [403 => '/denied', 404 => '/missing', 'front' => '/node']];

    $event = new ToolInputTransformEvent('site', $value, $tool, NULL, $definition);
    $dispatcher->dispatch($event);
    $this->assertSame($value, $event->getValue());

    $schema = $this->container->get('serializer')->normalize($definition, 'json_schema', [
      'tool_invoker' => new Invoker('test', InvokerCapability::EntitiesAsHandles),
      'tool_id' => 'no_requirements_tool',
      'tool_definition_name' => 'site',
      'tool_direction' => ToolDefinitionNormalizeDirection::Input,
    ]);
    $this->assertSame([403, 404, 'front'], array_keys($schema['properties']['page']['properties']));

    $output = new MapOutputDefinition('Pages', 'Error pages.', property_definitions: [
      403 => new OutputDefinition('string', 'Denied', 'The 403 page.'),
    ]);
    $output_event = new ToolOutputTransformEvent('pages', [403 => '/denied'], $tool, NULL, $output);
    $dispatcher->dispatch($output_event);
    $this->assertSame([403 => '/denied'], $output_event->getValue());
  }

  /**
   * Tests a config-schema sequence becomes a ListInputDefinition with items.
   *
   * @covers ::fromConfigSchema
   */
  public function testFromConfigSchemaSequence(): void {
    $definition = InputDefinition::fromConfigSchema([
      'type' => 'sequence',
      'label' => 'Tags',
      'sequence' => [
        'type' => 'string',
        'label' => 'Tag',
      ],
    ]);

    $this->assertInstanceOf(ListInputDefinition::class, $definition);
    $this->assertSame('string', $definition->getItemDefinition()->getDataType());
  }

  /**
   * Tests config-schema scalars resolve to their concrete data types.
   *
   * Config schema scalar types are wrapped classes that must unwrap to the
   * canonical typed-data type for the input definition.
   *
   * @covers ::fromConfigSchema
   */
  public function testFromConfigSchemaScalars(): void {
    $string = InputDefinition::fromConfigSchema(['type' => 'string', 'label' => 'Title']);
    $this->assertSame('string', $string->getDataType());

    $integer = InputDefinition::fromConfigSchema(['type' => 'integer', 'label' => 'Limit']);
    $this->assertSame('integer', $integer->getDataType());

    $boolean = InputDefinition::fromConfigSchema(['type' => 'boolean', 'label' => 'Enabled']);
    $this->assertSame('boolean', $boolean->getDataType());
  }

  /**
   * Tests a typed-data list definition converts its item definition.
   *
   * Regression test for #3582984: plain typed-data list item definitions
   * previously crashed the factory (missing label) and lost their data type
   * (missing 'type' key in toArray()).
   *
   * @covers ::fromDataDefinition
   */
  public function testFromDataDefinitionList(): void {
    $list = ListDataDefinition::create('string')
      ->setLabel('Tags');

    $definition = InputDefinition::fromDataDefinition($list);

    $this->assertInstanceOf(ListInputDefinition::class, $definition);
    $this->assertSame('string', $definition->getItemDefinition()->getDataType());
  }

  /**
   * Tests an unlabeled typed-data definition gets a generated label.
   *
   * Typed-data labels are optional; the factory must not crash and must
   * produce a usable placeholder (#3582984).
   *
   * @covers ::fromDataDefinition
   */
  public function testFromDataDefinitionWithoutLabel(): void {
    $definition = InputDefinition::fromDataDefinition(DataDefinition::create('integer'));

    $this->assertSame('integer', $definition->getDataType());
    $this->assertSame('integer input', (string) $definition->getLabel());
  }

  /**
   * Tests a typed-data scalar definition converts with its metadata.
   *
   * @covers ::fromDataDefinition
   */
  public function testFromDataDefinitionScalar(): void {
    $data_definition = DataDefinition::create('string')
      ->setLabel('Title')
      ->setDescription('The title.')
      ->setRequired(TRUE);

    $definition = InputDefinition::fromDataDefinition($data_definition);

    $this->assertSame('string', $definition->getDataType());
    $this->assertSame('Title', (string) $definition->getLabel());
    $this->assertSame('The title.', (string) $definition->getDescription());
    $this->assertTrue($definition->isRequired());
  }

  /**
   * Tests a typed-data map definition converts its properties.
   *
   * @covers ::fromDataDefinition
   */
  public function testFromDataDefinitionMap(): void {
    $map = MapDataDefinition::create()
      ->setLabel('Settings');
    $map->setPropertyDefinition('title', DataDefinition::create('string')->setLabel('Title'));
    $map->setPropertyDefinition('limit', DataDefinition::create('integer')->setLabel('Limit'));

    $definition = InputDefinition::fromDataDefinition($map);

    $this->assertInstanceOf(MapInputDefinition::class, $definition);
    $properties = $definition->getPropertyDefinitions();
    $this->assertSame(['title', 'limit'], array_keys($properties));
    $this->assertSame('integer', $properties['limit']->getDataType());
  }

  /**
   * Tests that a converted definition round-trips through typed data.
   *
   * The factory output must be usable as a live input definition: creating
   * typed data from it and validating a value must behave like a directly
   * declared definition.
   *
   * @covers ::fromConfigSchema
   */
  public function testConvertedDefinitionIsUsable(): void {
    $definition = InputDefinition::fromConfigSchema([
      'type' => 'tool_test.settings_fixture',
      'label' => 'Settings',
    ]);

    $tool = $this->container->get('plugin.manager.tool')->createInstance('no_requirements_tool');
    $violations = $tool->validateInputValue($definition, [
      'title' => 'Hello',
      'limit' => 5,
      'tags' => ['a', 'b'],
    ]);
    $this->assertCount(0, $violations);
  }

}
