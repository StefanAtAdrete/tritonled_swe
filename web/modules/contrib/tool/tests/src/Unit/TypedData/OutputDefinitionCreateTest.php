<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\TypedData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the OutputDefinition family and its type boundary (#3583015).
 *
 * @group tool
 */
class OutputDefinitionCreateTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests creating a scalar output definition.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::create
   */
  public function testCreateScalar(): void {
    $definition = OutputDefinition::create('string');
    $this->assertInstanceOf(OutputDefinition::class, $definition);
    $this->assertSame('string', $definition->getDataType());
  }

  /**
   * Tests the entity branch returns an output definition.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::create
   */
  public function testCreateEntityReturnsOutputDefinition(): void {
    $definition = OutputDefinition::create('entity:node');
    $this->assertInstanceOf(EntityOutputDefinition::class, $definition);
    $this->assertInstanceOf(OutputDefinitionInterface::class, $definition);
    $this->assertSame('entity:node', $definition->getDataType());
  }

  /**
   * Tests the entity factory accepts both prefixed and bare forms.
   *
   * @covers \Drupal\tool\TypedData\EntityOutputDefinition::create
   */
  public function testEntityCreateNormalizesPrefix(): void {
    $this->assertSame('entity:node', EntityOutputDefinition::create('node')->getDataType());
    $this->assertSame('entity:node', EntityOutputDefinition::create('entity:node')->getDataType());
  }

  /**
   * Tests the structured definition factories construct their own class.
   *
   * @covers \Drupal\tool\TypedData\MapOutputDefinition::create
   * @covers \Drupal\tool\TypedData\ListOutputDefinition::create
   */
  public function testStructuredCreateFactories(): void {
    $map = MapOutputDefinition::create();
    $this->assertInstanceOf(MapOutputDefinition::class, $map);
    $this->assertSame('map', $map->getDataType());

    $list = ListOutputDefinition::create();
    $this->assertInstanceOf(ListOutputDefinition::class, $list);
    $this->assertSame('list', $list->getDataType());
  }

  /**
   * Tests the constructor argument order matches InputDefinition.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::__construct
   */
  public function testConstructorMirrorsInputDefinition(): void {
    $definition = new OutputDefinition('integer', 'Count', 'How many.', FALSE, TRUE, 3, ['Range' => ['min' => 0]]);
    $this->assertSame('integer', $definition->getDataType());
    $this->assertSame('Count', $definition->getLabel());
    $this->assertSame('How many.', $definition->getDescription());
    $this->assertFalse($definition->isRequired());
    $this->assertTrue($definition->isMultiple());
    $this->assertSame(3, $definition->getDefaultValue());
    $this->assertArrayHasKey('Range', $definition->getConstraints());
  }

  /**
   * Tests map properties must be output definitions.
   *
   * @covers \Drupal\tool\TypedData\MapOutputDefinition::__construct
   */
  public function testMapRejectsCoreProperty(): void {
    $this->expectException(\InvalidArgumentException::class);
    new MapOutputDefinition('Report', 'A report.', property_definitions: [
      'summary' => new ContextDefinition('string', 'Summary'),
    ]);
  }

  /**
   * Tests the map setter enforces the same boundary as its constructor.
   *
   * @covers \Drupal\tool\TypedData\MapOutputDefinition::setPropertyDefinitions
   */
  public function testMapSetterRejectsCoreProperty(): void {
    $this->expectException(\InvalidArgumentException::class);
    (new MapOutputDefinition('Report', 'A report.'))->setPropertyDefinitions([
      'summary' => new ContextDefinition('string', 'Summary'),
    ]);
  }

  /**
   * Tests the single-property setter enforces the same boundary.
   *
   * @covers \Drupal\tool\TypedData\MapDefinitionBase::setPropertyDefinition
   */
  public function testMapPropertySetterRejectsInputProperty(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Property definitions must be instances of OutputDefinitionInterface.');
    (new MapOutputDefinition('Report', 'A report.'))->setPropertyDefinition('summary', new InputDefinition('string', 'Summary', 'S.'));
  }

  /**
   * Tests the list setter enforces the same boundary as its constructor.
   *
   * @covers \Drupal\tool\TypedData\ListOutputDefinition::setItemDefinition
   */
  public function testListSetterRejectsCoreItem(): void {
    $this->expectException(\TypeError::class);
    (new ListOutputDefinition('Tags', 'Tags.'))->setItemDefinition(new ContextDefinition('string', 'Tag'));
  }

  /**
   * Tests a list without an item definition still yields an output item.
   *
   * @covers \Drupal\tool\TypedData\ListOutputDefinition::getItemDefinition
   */
  public function testListDefaultItemIsOutputDefinition(): void {
    $item = (new ListOutputDefinition('Tags', 'Tags.'))->getItemDefinition();
    $this->assertInstanceOf(OutputDefinition::class, $item);
    $this->assertSame('any', $item->getDataType());
  }

  /**
   * Tests a tool definition converts a bare core output with a deprecation.
   *
   * A multiple core output becomes a list output of its data type
   * (#3583049).
   *
   * @covers \Drupal\tool\Tool\ToolDefinition::__construct
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  #[IgnoreDeprecations]
  public function testToolDefinitionBridgesCoreOutput(): void {
    $this->expectDeprecation("Declaring the 'message' output with Drupal\\Core\\Plugin\\Context\\ContextDefinition is deprecated in tool:1.0.0-beta9 and is removed from tool:1.0.0-rc1. Use an OutputDefinitionInterface class instead. See https://git.drupalcode.org/project/tool/-/work_items/3583050");
    $definition = new ToolDefinition([
      'id' => 'test',
      'output_definitions' => [
        'message' => new ContextDefinition('string', 'Message', required: FALSE, multiple: TRUE),
      ],
    ]);
    $output = $definition->getOutputDefinition('message');
    $this->assertInstanceOf(ListOutputDefinition::class, $output);
    $this->assertSame('list', $output->getDataType());
    $this->assertSame('Message', (string) $output->getLabel());
    $this->assertSame('The Message output.', (string) $output->getDescription());
    $this->assertFalse($output->isRequired());
    $this->assertFalse($output->isMultiple());
    $item = $output->getItemDefinition();
    $this->assertInstanceOf(OutputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());
    $this->assertFalse($item->isMultiple());
  }

  /**
   * Tests the bridge converts a multiple definition to a list output.
   *
   * Count describes the list and moves to it; every other constraint stays
   * on the item, which is required and has no label or description of its
   * own, as the normalizer describes the item of a multiple definition. The
   * bridge converts the way a tool definition converts a multiple output,
   * so the list is marked as converted.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgeConvertsMultipleToList(): void {
    $core = new ContextDefinition('string', 'Tags', required: FALSE, multiple: TRUE, description: 'The tags.', default_value: ['north'], constraints: [
      'Count' => ['max' => 3],
      'Length' => ['min' => 3],
    ]);
    $output = OutputDefinition::fromContextDefinition($core, 'tags');
    $this->assertInstanceOf(ListOutputDefinition::class, $output);
    $this->assertTrue($output->isConvertedFromMultiple());
    $this->assertFalse($output->isMultiple());
    $this->assertSame('Tags', (string) $output->getLabel());
    $this->assertSame('The tags.', (string) $output->getDescription());
    $this->assertFalse($output->isRequired());
    $this->assertSame(['north'], $output->getDefaultValue());
    $this->assertSame(['Count' => ['max' => 3]], $output->getConstraints());

    $item = $output->getItemDefinition();
    $this->assertInstanceOf(OutputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
    $this->assertTrue($item->isRequired());
    $this->assertFalse($item->isMultiple());
    $this->assertNull($item->getDefaultValue());
    $this->assertSame('', (string) $item->getLabel());
    $this->assertSame('', (string) $item->getDescription());

    // The item keeps the class of the data type it describes.
    $entities = OutputDefinition::fromContextDefinition(new EntityContextDefinition('node', 'Nodes', multiple: TRUE), 'nodes');
    $this->assertInstanceOf(ListOutputDefinition::class, $entities);
    $this->assertInstanceOf(EntityOutputDefinition::class, $entities->getItemDefinition());
    $this->assertSame('entity:node', $entities->getItemDefinition()->getDataType());

    // A multiple map definition declared where an output belongs keeps its
    // properties on the item.
    $maps = new MapInputDefinition('Variants', 'V.', multiple: TRUE, property_definitions: [
      'sku' => new InputDefinition('string', 'SKU', 'S.'),
    ]);
    $output = OutputDefinition::fromContextDefinition($maps, 'variants');
    $this->assertInstanceOf(ListOutputDefinition::class, $output);
    $item = $output->getItemDefinition();
    $this->assertInstanceOf(MapOutputDefinition::class, $item);
    $this->assertInstanceOf(OutputDefinition::class, $item->getPropertyDefinition('sku'));
  }

  /**
   * Tests the bridge keeps an existing description and constraints.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgeKeepsDescriptionAndConstraints(): void {
    $core = new ContextDefinition('integer', 'Count', description: 'How many.', default_value: 3, constraints: ['Range' => ['min' => 0]]);
    $output = OutputDefinition::fromContextDefinition($core, 'count');
    $this->assertSame('How many.', (string) $output->getDescription());
    $this->assertSame(3, $output->getDefaultValue());
    $this->assertSame(['Range' => ['min' => 0]], $output->getConstraints());
  }

  /**
   * Tests the bridge falls back to the output name when the label is empty.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgeUsesNameWithoutLabel(): void {
    $output = OutputDefinition::fromContextDefinition(new ContextDefinition('string'), 'result');
    $this->assertSame('result', (string) $output->getLabel());
    $this->assertSame('The result output.', (string) $output->getDescription());
  }

  /**
   * Tests the synthesized description does not HTML-escape the label.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgeSynthesizedDescriptionIsNotEscaped(): void {
    $output = OutputDefinition::fromContextDefinition(new ContextDefinition('string', "Node's title & summary"), 'summary');
    $description = (string) $output->getDescription();
    $this->assertSame("The Node's title & summary output.", $description);
    $this->assertStringNotContainsString('&#039;', $description);
    $this->assertStringNotContainsString('&amp;', $description);
  }

  /**
   * Tests the bridge returns an output definition unchanged.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgePassesOutputDefinitionThrough(): void {
    $output = new OutputDefinition('string', 'Message', 'The message.');
    $this->assertSame($output, OutputDefinition::fromContextDefinition($output, 'message'));
  }

  /**
   * Tests the bridge converts entity definitions to the entity output class.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgeConvertsEntity(): void {
    $core = new EntityContextDefinition('node', new TranslatableMarkup('Node'), constraints: ['Bundle' => ['article']]);
    $output = OutputDefinition::fromContextDefinition($core, 'node');
    $this->assertInstanceOf(EntityOutputDefinition::class, $output);
    $this->assertSame('entity:node', $output->getDataType());
    $this->assertSame(['Bundle' => ['article']], $output->getConstraints());
    $this->assertSame('The Node output.', (string) $output->getDescription());
  }

  /**
   * Tests the bridge keeps the structure of input-family definitions.
   *
   * The bridge matches definitions by their typed-data parents, so a map
   * output declared with MapInputDefinition keeps its properties.
   *
   * @covers \Drupal\tool\TypedData\OutputDefinition::fromContextDefinition
   */
  public function testBridgeConvertsInputFamily(): void {
    $map = new MapInputDefinition('Report', 'R.', property_definitions: [
      'summary' => new InputDefinition('string', 'Summary', 'S.'),
    ]);
    $output = OutputDefinition::fromContextDefinition($map, 'report');
    $this->assertInstanceOf(MapOutputDefinition::class, $output);
    $summary = $output->getPropertyDefinition('summary');
    $this->assertInstanceOf(OutputDefinition::class, $summary);
    $this->assertSame('string', $summary->getDataType());

    $list = new ListInputDefinition('Tags', 'T.', item_definition: new InputDefinition('string', 'Tag', 'T.'));
    $output = OutputDefinition::fromContextDefinition($list, 'tags');
    $this->assertInstanceOf(ListOutputDefinition::class, $output);
    $this->assertSame('string', $output->getItemDefinition()->getDataType());

    $output = OutputDefinition::fromContextDefinition(new EntityInputDefinition('node', 'Node', 'N.'), 'node');
    $this->assertInstanceOf(EntityOutputDefinition::class, $output);
    $this->assertSame('entity:node', $output->getDataType());
  }

}
