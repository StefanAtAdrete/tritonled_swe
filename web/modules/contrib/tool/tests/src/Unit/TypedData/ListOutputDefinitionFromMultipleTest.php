<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\TypedData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests converting a multiple output definition to a list (#3583049).
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedData\ListOutputDefinition
 */
class ListOutputDefinitionFromMultipleTest extends UnitTestCase {

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
   * Builds a multiple output constraining both the list and its items.
   *
   * @return \Drupal\tool\TypedData\OutputDefinition
   *   The multiple output definition.
   */
  protected function multipleTags(): OutputDefinition {
    return new OutputDefinition(
      data_type: 'string',
      label: 'Tags',
      description: 'Tags.',
      required: FALSE,
      multiple: TRUE,
      default_value: ['north', 'south'],
      constraints: ['Count' => ['min' => 2], 'Length' => ['min' => 3]],
      examples: ['alpha'],
    );
  }

  /**
   * Tests Count moves to the list and every other constraint to the item.
   *
   * @covers ::fromMultiple
   */
  public function testRoutesConstraints(): void {
    $list = ListOutputDefinition::fromMultiple($this->multipleTags());
    $this->assertSame(['Count' => ['min' => 2]], $list->getConstraints());

    $item = $list->getItemDefinition();
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
    $this->assertTrue($item->isRequired());
    $this->assertFalse($item->isMultiple());

    // Without Count the list declares no constraints of its own.
    $list = ListOutputDefinition::fromMultiple(new OutputDefinition('integer', 'Sizes', 'S.', multiple: TRUE, constraints: ['Range' => ['min' => 1]]));
    $this->assertSame([], $list->getConstraints());
    $this->assertSame(['Range' => ['min' => 1]], $list->getItemDefinition()->getConstraints());
  }

  /**
   * Tests a not-nullable declaration implies nothing for the list.
   *
   * A multiple's constraints bind its items; the list carries only a
   * declared Count. A required list may be empty, and "must not be empty"
   * is a NotBlank or Count declared on the list itself (#3583069).
   *
   * @covers ::fromMultiple
   * @covers \Drupal\tool\TypedData\ListDefinitionBase::splitMultipleConstraints
   */
  public function testImpliesNothingForTheList(): void {
    $list = ListOutputDefinition::fromMultiple(new OutputDefinition('integer', 'Sizes', 'S.', required: FALSE, multiple: TRUE, constraints: [
      'NotNull' => [],
      'Range' => ['min' => 1],
    ]));
    $this->assertSame([], $list->getConstraints());
    $this->assertSame(['NotNull' => [], 'Range' => ['min' => 1]], $list->getItemDefinition()->getConstraints());

    $list = ListOutputDefinition::fromMultiple(new OutputDefinition('string', 'Names', 'N.', required: FALSE, multiple: TRUE, constraints: ['NotBlank' => []]));
    $this->assertSame([], $list->getConstraints());
    $this->assertSame(['NotBlank' => []], $list->getItemDefinition()->getConstraints());

    $list = ListOutputDefinition::fromMultiple(new OutputDefinition('string', 'Names', 'N.', required: FALSE, multiple: TRUE, constraints: ['NotBlank' => ['allowNull' => TRUE]]));
    $this->assertSame([], $list->getConstraints());
  }

  /**
   * Tests the list carries the definition-level state of the declaration.
   *
   * @covers ::fromMultiple
   * @covers \Drupal\tool\TypedData\ListDefinitionBase::isConvertedFromMultiple
   */
  public function testCarriesDefinitionState(): void {
    $list = ListOutputDefinition::fromMultiple($this->multipleTags());
    $this->assertSame('list', $list->getDataType());
    $this->assertSame('Tags', $list->getLabel());
    $this->assertSame('Tags.', $list->getDescription());
    $this->assertFalse($list->isRequired());
    $this->assertFalse($list->isMultiple());
    $this->assertSame(['north', 'south'], $list->getDefaultValue());
    $this->assertSame([], $list->getExamples());
    $this->assertTrue($list->isConvertedFromMultiple());
    $this->assertTrue((clone $list)->isConvertedFromMultiple());

    // The item describes a single value: it keeps the examples and has no
    // label, description or default value of its own.
    $item = $list->getItemDefinition();
    $this->assertSame('', $item->getLabel());
    $this->assertSame('', $item->getDescription());
    $this->assertNull($item->getDefaultValue());
    $this->assertSame(['alpha'], $item->getExamples());

    // A required declaration gives a required list, and translatable
    // labels are kept as they are.
    $label = new TranslatableMarkup('Codes');
    $description = new TranslatableMarkup('Some codes.');
    $list = ListOutputDefinition::fromMultiple(new OutputDefinition('string', $label, $description, multiple: TRUE));
    $this->assertTrue($list->isRequired());
    $this->assertSame($label, $list->getLabel());
    $this->assertSame($description, $list->getDescription());

    // A list declared as a list is not marked as converted.
    $this->assertFalse((new ListOutputDefinition('Tags', 'T.'))->isConvertedFromMultiple());
  }

  /**
   * Tests the item keeps the data type and structure of the declaration.
   *
   * @covers ::fromMultiple
   */
  public function testItemKeepsDataType(): void {
    $item = ListOutputDefinition::fromMultiple($this->multipleTags())->getItemDefinition();
    $this->assertInstanceOf(OutputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());

    $item = ListOutputDefinition::fromMultiple(new EntityOutputDefinition('node', 'Nodes', 'N.', multiple: TRUE))->getItemDefinition();
    $this->assertInstanceOf(EntityOutputDefinition::class, $item);
    $this->assertSame('entity:node', $item->getDataType());

    $maps = new MapOutputDefinition('Variants', 'V.', multiple: TRUE, property_definitions: [
      'sku' => new OutputDefinition('string', 'SKU', 'S.'),
    ]);
    $item = ListOutputDefinition::fromMultiple($maps)->getItemDefinition();
    $this->assertInstanceOf(MapOutputDefinition::class, $item);
    $this->assertSame(['sku'], array_keys($item->getPropertyDefinitions()));
  }

  /**
   * Tests the declaration itself is left unchanged.
   *
   * @covers ::fromMultiple
   */
  public function testLeavesDeclarationUnchanged(): void {
    $multiple = $this->multipleTags();
    ListOutputDefinition::fromMultiple($multiple);
    $this->assertTrue($multiple->isMultiple());
    $this->assertFalse($multiple->isRequired());
    $this->assertSame('Tags', $multiple->getLabel());
    $this->assertSame(['north', 'south'], $multiple->getDefaultValue());
    $this->assertSame(['Count' => ['min' => 2], 'Length' => ['min' => 3]], $multiple->getConstraints());
    $this->assertSame(['alpha'], $multiple->getExamples());
  }

  /**
   * Tests a tool definition converts a multiple output with a deprecation.
   *
   * @covers \Drupal\tool\Tool\ToolDefinition::addOutputDefinition
   */
  #[IgnoreDeprecations]
  public function testToolDefinitionConvertsMultipleOutput(): void {
    $this->expectDeprecation("Declaring the 'tags' output with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListOutputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $definition = new ToolDefinition([
      'id' => 'test',
      'output_definitions' => [
        'tags' => $this->multipleTags(),
      ],
    ]);
    $output = $definition->getOutputDefinition('tags');
    $this->assertInstanceOf(ListOutputDefinition::class, $output);
    $this->assertFalse($output->isMultiple());
    $this->assertTrue($output->isConvertedFromMultiple());
    $this->assertSame(['Count' => ['min' => 2]], $output->getConstraints());
    $item = $output->getItemDefinition();
    $this->assertSame('string', $item->getDataType());
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
  }

  /**
   * Tests a tool definition stores every other output unchanged.
   *
   * A list definition marked multiple is left as it is: it would be a list
   * of lists, which a list definition declares explicitly instead.
   *
   * @covers \Drupal\tool\Tool\ToolDefinition::addOutputDefinition
   */
  public function testToolDefinitionKeepsOtherOutputs(): void {
    $single = new OutputDefinition('string', 'Name', 'N.');
    $list = new ListOutputDefinition('Tags', 'T.', item_definition: new OutputDefinition('string', 'Tag', 'T.'));
    $multiple_list = (new ListOutputDefinition('Rows', 'R.'))->setMultiple();
    $definition = (new ToolDefinition(['id' => 'test']))
      ->addOutputDefinition('name', $single)
      ->addOutputDefinition('tags', $list)
      ->addOutputDefinition('rows', $multiple_list);
    $this->assertSame($single, $definition->getOutputDefinition('name'));
    $this->assertSame($list, $definition->getOutputDefinition('tags'));
    $this->assertSame($multiple_list, $definition->getOutputDefinition('rows'));
  }

}
