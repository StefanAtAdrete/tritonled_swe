<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\TypedData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests converting a multiple input definition to a list (#3583049).
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedData\ListInputDefinition
 */
class ListInputDefinitionFromMultipleTest extends UnitTestCase {

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
   * Builds a multiple input constraining both the list and its items.
   *
   * @return \Drupal\tool\TypedData\InputDefinition
   *   The multiple input definition.
   */
  protected function multipleTags(): InputDefinition {
    return new InputDefinition(
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
    $list = ListInputDefinition::fromMultiple($this->multipleTags());
    $this->assertSame(['Count' => ['min' => 2]], $list->getConstraints());

    $item = $list->getItemDefinition();
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
    $this->assertTrue($item->isRequired());
    $this->assertFalse($item->isMultiple());

    // Without Count the list declares no constraints of its own.
    $list = ListInputDefinition::fromMultiple(new InputDefinition('integer', 'Sizes', 'S.', multiple: TRUE, constraints: ['Range' => ['min' => 1]]));
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
    $list = ListInputDefinition::fromMultiple(new InputDefinition('integer', 'Sizes', 'S.', required: FALSE, multiple: TRUE, constraints: [
      'NotNull' => [],
      'Range' => ['min' => 1],
    ]));
    $this->assertSame([], $list->getConstraints());
    $this->assertSame(['NotNull' => [], 'Range' => ['min' => 1]], $list->getItemDefinition()->getConstraints());

    $list = ListInputDefinition::fromMultiple(new InputDefinition('string', 'Names', 'N.', required: FALSE, multiple: TRUE, constraints: ['NotBlank' => []]));
    $this->assertSame([], $list->getConstraints());
    $this->assertSame(['NotBlank' => []], $list->getItemDefinition()->getConstraints());

    $list = ListInputDefinition::fromMultiple(new InputDefinition('string', 'Names', 'N.', required: FALSE, multiple: TRUE, constraints: ['NotBlank' => ['allowNull' => TRUE]]));
    $this->assertSame([], $list->getConstraints());
  }

  /**
   * Tests the list carries the definition-level state of the declaration.
   *
   * @covers ::fromMultiple
   */
  public function testCarriesDefinitionState(): void {
    $multiple = $this->multipleTags();
    $multiple->setLocked();
    $list = ListInputDefinition::fromMultiple($multiple);
    $this->assertSame('list', $list->getDataType());
    $this->assertSame('Tags', $list->getLabel());
    $this->assertSame('Tags.', $list->getDescription());
    $this->assertFalse($list->isRequired());
    $this->assertFalse($list->isMultiple());
    $this->assertSame(['north', 'south'], $list->getDefaultValue());
    $this->assertTrue($list->isLocked());
    $this->assertSame([], $list->getExamples());

    // The item describes a single value: it keeps the examples and has no
    // label, description, default value or lock of its own.
    $item = $list->getItemDefinition();
    $this->assertSame('', $item->getLabel());
    $this->assertSame('', $item->getDescription());
    $this->assertNull($item->getDefaultValue());
    $this->assertFalse($item->isLocked());
    $this->assertSame(['alpha'], $item->getExamples());

    // A required declaration gives a required list, and translatable
    // labels are kept as they are.
    $label = new TranslatableMarkup('Codes');
    $description = new TranslatableMarkup('Some codes.');
    $list = ListInputDefinition::fromMultiple(new InputDefinition('string', $label, $description, multiple: TRUE));
    $this->assertTrue($list->isRequired());
    $this->assertSame($label, $list->getLabel());
    $this->assertSame($description, $list->getDescription());
  }

  /**
   * Tests the item keeps the data type and structure of the declaration.
   *
   * @covers ::fromMultiple
   */
  public function testItemKeepsDataType(): void {
    $item = ListInputDefinition::fromMultiple($this->multipleTags())->getItemDefinition();
    $this->assertInstanceOf(InputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());

    $item = ListInputDefinition::fromMultiple(new EntityInputDefinition('node', 'Nodes', 'N.', multiple: TRUE))->getItemDefinition();
    $this->assertInstanceOf(EntityInputDefinition::class, $item);
    $this->assertSame('entity:node', $item->getDataType());

    $maps = new MapInputDefinition('Variants', 'V.', multiple: TRUE, property_definitions: [
      'sku' => new InputDefinition('string', 'SKU', 'S.'),
    ]);
    $item = ListInputDefinition::fromMultiple($maps)->getItemDefinition();
    $this->assertInstanceOf(MapInputDefinition::class, $item);
    $this->assertSame(['sku'], array_keys($item->getPropertyDefinitions()));
  }

  /**
   * Tests the declaration itself is left unchanged.
   *
   * @covers ::fromMultiple
   */
  public function testLeavesDeclarationUnchanged(): void {
    $multiple = $this->multipleTags();
    ListInputDefinition::fromMultiple($multiple);
    $this->assertTrue($multiple->isMultiple());
    $this->assertFalse($multiple->isRequired());
    $this->assertSame('Tags', $multiple->getLabel());
    $this->assertSame(['north', 'south'], $multiple->getDefaultValue());
    $this->assertSame(['Count' => ['min' => 2], 'Length' => ['min' => 3]], $multiple->getConstraints());
  }

  /**
   * Tests a tool definition converts a multiple input with a deprecation.
   *
   * @covers \Drupal\tool\Tool\ToolDefinition::addInputDefinition
   */
  #[IgnoreDeprecations]
  public function testToolDefinitionConvertsMultipleInput(): void {
    $this->expectDeprecation("Declaring the 'tags' input with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $definition = new ToolDefinition([
      'id' => 'test',
      'input_definitions' => [
        'tags' => $this->multipleTags(),
      ],
    ]);
    $input = $definition->getInputDefinition('tags');
    $this->assertInstanceOf(ListInputDefinition::class, $input);
    $this->assertFalse($input->isMultiple());
    $this->assertSame(['Count' => ['min' => 2]], $input->getConstraints());
    $item = $input->getItemDefinition();
    $this->assertSame('string', $item->getDataType());
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
  }

  /**
   * Tests a tool definition stores every other input unchanged.
   *
   * A list definition marked multiple is left as it is: it would be a list
   * of lists, which a list definition declares explicitly instead.
   *
   * @covers \Drupal\tool\Tool\ToolDefinition::addInputDefinition
   */
  public function testToolDefinitionKeepsOtherInputs(): void {
    $single = new InputDefinition('string', 'Name', 'N.');
    $list = new ListInputDefinition('Tags', 'T.', item_definition: new InputDefinition('string', 'Tag', 'T.'));
    $multiple_list = (new ListInputDefinition('Rows', 'R.'))->setMultiple();
    $definition = (new ToolDefinition(['id' => 'test']))
      ->addInputDefinition('name', $single)
      ->addInputDefinition('tags', $list)
      ->addInputDefinition('rows', $multiple_list);
    $this->assertSame($single, $definition->getInputDefinition('name'));
    $this->assertSame($list, $definition->getInputDefinition('tags'));
    $this->assertSame($multiple_list, $definition->getInputDefinition('rows'));
  }

}
