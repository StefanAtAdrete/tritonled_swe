<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Normalizer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolDefinitionNormalizeDirection;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests a multiple definition advertises the schema of its list (#3583049).
 *
 * Declaring multiple: TRUE is deprecated in favor of a list definition, and
 * the declaration is converted to that list. The conversion must not change
 * what invokers are told: the normalizer sends Count on a multiple
 * definition to the array and every other constraint to the items, and the
 * converted list places them the same way. Member order is not compared,
 * as it carries no meaning in a JSON object.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class MultipleListSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'node',
    'field',
    'text',
  ];

  /**
   * Provides the multiple input declarations to compare.
   *
   * Definitions are built in multipleInput(): the provider data must cross
   * the process isolation boundary.
   *
   * @return \Generator
   *   Cases of [declaration name].
   */
  public static function multipleInputProvider(): \Generator {
    yield 'required scalar' => ['required_scalar'];
    yield 'count and item constraints' => ['count_and_item_constraints'];
    yield 'exact count and choices' => ['exact_count'];
    yield 'examples' => ['examples'];
    yield 'optional but not nullable' => ['not_nullable'];
    yield 'not blank' => ['not_blank'];
    yield 'not blank allowing null' => ['not_blank_allowing_null'];
    yield 'datetime marker' => ['datetime_marker'];
    yield 'entity' => ['entity'];
    yield 'map with a list property' => ['map'];
  }

  /**
   * Builds a multiple input declaration.
   *
   * @param string $name
   *   The declaration name from the provider.
   *
   * @return \Drupal\tool\TypedData\InputDefinitionInterface
   *   The multiple input definition.
   */
  protected function multipleInput(string $name): InputDefinitionInterface {
    return match ($name) {
      'required_scalar' => new InputDefinition('string', 'Tags', 'Some tags.', multiple: TRUE),
      'count_and_item_constraints' => new InputDefinition('string', 'Tags', 'Some tags.', required: FALSE, multiple: TRUE, constraints: [
        'Count' => ['min' => 2, 'max' => 4],
        'Length' => ['min' => 3],
        'Regex' => ['pattern' => '/^[a-z]+$/'],
      ]),
      'exact_count' => new InputDefinition('integer', 'Levels', 'Some levels.', required: FALSE, multiple: TRUE, constraints: [
        'Count' => ['exactly' => 2],
        'Choice' => ['choices' => [1, 2, 3]],
      ]),
      'examples' => new InputDefinition('string', 'Aliases', 'Some aliases.', required: FALSE, multiple: TRUE, examples: [
        'alpha',
        'beta',
      ]),
      'not_nullable' => new InputDefinition('integer', 'Sizes', 'Some sizes.', required: FALSE, multiple: TRUE, constraints: [
        'NotNull' => [],
        'Range' => ['min' => 1],
      ]),
      'not_blank' => new InputDefinition('string', 'Names', 'Some names.', required: FALSE, multiple: TRUE, constraints: ['NotBlank' => []]),
      'not_blank_allowing_null' => new InputDefinition('string', 'Names', 'Some names.', required: FALSE, multiple: TRUE, constraints: [
        'NotBlank' => ['allowNull' => TRUE],
      ]),
      'datetime_marker' => new InputDefinition('datetime_iso8601', 'Dates', 'Some dates.', required: FALSE, multiple: TRUE, constraints: ['DateTime' => []]),
      'entity' => new EntityInputDefinition('node', 'Nodes', 'Some nodes.', required: FALSE, multiple: TRUE),
      'map' => new MapInputDefinition('Variants', 'Some variants.', required: FALSE, multiple: TRUE, property_definitions: [
        'sku' => new InputDefinition('string', 'SKU', 'A stock keeping unit.', required: FALSE),
        'sizes' => new ListInputDefinition('Sizes', 'Some sizes.', required: FALSE, item_definition: new InputDefinition('integer', 'Size', 'A size.')),
      ]),
    };
  }

  /**
   * Tests a multiple input and the list it converts to share a schema.
   *
   * @param string $name
   *   The declaration name.
   *
   * @dataProvider multipleInputProvider
   */
  #[DataProvider('multipleInputProvider')]
  public function testInputSchemaUnchanged(string $name): void {
    $multiple = $this->multipleInput($name);
    $list = ListInputDefinition::fromMultiple($multiple);
    $this->assertInstanceOf(ListInputDefinition::class, $list);
    $this->assertSameSchema($this->normalize($multiple), $this->normalize($list));
  }

  /**
   * Tests a default value leaves the items of the schema.
   *
   * The normalizer advertised the default of a multiple definition on each
   * item, where a whole list does not fit. The list keeps the default and,
   * like any list definition, does not advertise it, so this is the one
   * difference between the two schemas.
   */
  public function testDefaultValueLeavesItems(): void {
    $multiple = new InputDefinition('string', 'Aliases', 'Some aliases.', required: FALSE, multiple: TRUE, default_value: ['alpha']);
    $list = ListInputDefinition::fromMultiple($multiple);
    $this->assertSame(['alpha'], $list->getDefaultValue());

    $expected = $this->normalize($multiple);
    $this->assertSame(['alpha'], $expected['items']['default']);
    unset($expected['items']['default']);
    $this->assertSameSchema($expected, $this->normalize($list));
  }

  /**
   * Provides the multiple output declarations to compare.
   *
   * Definitions are built in multipleOutput(): the provider data must cross
   * the process isolation boundary.
   *
   * @return \Generator
   *   Cases of [declaration name].
   */
  public static function multipleOutputProvider(): \Generator {
    yield 'required scalar' => ['required_scalar'];
    yield 'count and item constraints' => ['count_and_item_constraints'];
    yield 'examples' => ['examples'];
    yield 'optional but not nullable' => ['not_nullable'];
    yield 'entity' => ['entity'];
    yield 'map' => ['map'];
  }

  /**
   * Builds a multiple output declaration.
   *
   * @param string $name
   *   The declaration name from the provider.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The multiple output definition.
   */
  protected function multipleOutput(string $name): OutputDefinitionInterface {
    return match ($name) {
      'required_scalar' => new OutputDefinition('string', 'Tags', 'Some tags.', multiple: TRUE),
      'count_and_item_constraints' => new OutputDefinition('string', 'Tags', 'Some tags.', required: FALSE, multiple: TRUE, constraints: [
        'Count' => ['min' => 2, 'max' => 4],
        'Length' => ['min' => 3],
      ]),
      'examples' => new OutputDefinition('string', 'Aliases', 'Some aliases.', required: FALSE, multiple: TRUE, examples: [
        'alpha',
        'beta',
      ]),
      'not_nullable' => new OutputDefinition('integer', 'Sizes', 'Some sizes.', required: FALSE, multiple: TRUE, constraints: [
        'NotNull' => [],
        'Range' => ['min' => 1],
      ]),
      'entity' => new EntityOutputDefinition('node', 'Nodes', 'Some nodes.', required: FALSE, multiple: TRUE),
      'map' => new MapOutputDefinition('Variants', 'Some variants.', required: FALSE, multiple: TRUE, property_definitions: [
        'sku' => new OutputDefinition('string', 'SKU', 'A stock keeping unit.', required: FALSE),
      ]),
    };
  }

  /**
   * Tests a native multiple output keeps its schema once converted.
   *
   * A tool definition converts an output declared with multiple: TRUE to a
   * list with a deprecation, as it converts a multiple input.
   *
   * @param string $name
   *   The declaration name.
   *
   * @dataProvider multipleOutputProvider
   */
  #[DataProvider('multipleOutputProvider')]
  #[IgnoreDeprecations]
  public function testNativeOutputSchemaUnchanged(string $name): void {
    $this->expectDeprecation("Declaring the 'result' output with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListOutputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $multiple = $this->multipleOutput($name);
    $list = (new ToolDefinition(['id' => 'converted_tool']))
      ->addOutputDefinition('result', $multiple)
      ->getOutputDefinition('result');
    $this->assertInstanceOf(ListOutputDefinition::class, $list);
    $this->assertTrue($multiple->isMultiple());
    $this->assertSameSchema($this->normalize($multiple), $this->normalize($list));
  }

  /**
   * Provides the map families to declare a multiple property on.
   *
   * @return \Generator
   *   Cases of [family].
   */
  public static function mapPropertyProvider(): \Generator {
    yield 'input map' => ['input'];
    yield 'output map' => ['output'];
  }

  /**
   * Tests a multiple map property keeps its schema once converted.
   *
   * A map converts a property declared with multiple: TRUE to a list with a
   * deprecation. The property advertises the schema the multiple property
   * advertised, and the map the schema of a map declaring the list in the
   * shape of the conversion by hand.
   *
   * @param string $family
   *   The map family: 'input' or 'output'.
   *
   * @dataProvider mapPropertyProvider
   */
  #[DataProvider('mapPropertyProvider')]
  #[IgnoreDeprecations]
  public function testMapPropertySchemaUnchanged(string $family): void {
    $input = $family === 'input';
    $this->expectDeprecation(sprintf("Declaring the 'tags' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a %s instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049", $input ? 'ListInputDefinition' : 'ListOutputDefinition'));
    $constraints = ['Count' => ['max' => 3], 'Length' => ['min' => 3]];
    $multiple = $input
      ? new InputDefinition('string', 'Tags', 'Some tags.', required: FALSE, multiple: TRUE, constraints: $constraints)
      : new OutputDefinition('string', 'Tags', 'Some tags.', required: FALSE, multiple: TRUE, constraints: $constraints);
    $map = $input
      ? new MapInputDefinition('Profile', 'A profile.', required: FALSE, property_definitions: ['tags' => $multiple])
      : new MapOutputDefinition('Stats', 'Some stats.', required: FALSE, property_definitions: ['tags' => $multiple]);
    $this->assertInstanceOf($input ? ListInputDefinition::class : ListOutputDefinition::class, $map->getPropertyDefinition('tags'));
    $schema = $this->normalize($map);
    $this->assertSameSchema($this->normalize($multiple), $schema['properties']['tags']);

    $by_hand = $input
      ? new MapInputDefinition('Profile', 'A profile.', required: FALSE, property_definitions: [
        'tags' => new ListInputDefinition('Tags', 'Some tags.', required: FALSE, constraints: ['Count' => ['max' => 3]], item_definition: new InputDefinition('string', '', '', constraints: ['Length' => ['min' => 3]])),
      ])
      : new MapOutputDefinition('Stats', 'Some stats.', required: FALSE, property_definitions: [
        'tags' => new ListOutputDefinition('Tags', 'Some tags.', required: FALSE, constraints: ['Count' => ['max' => 3]], item_definition: new OutputDefinition('string', '', '', constraints: ['Length' => ['min' => 3]])),
      ]);
    $this->assertSameSchema($this->normalize($by_hand), $schema);
  }

  /**
   * Tests a multiple core output keeps the schema the bridge gave it.
   *
   * Before #3583049 the output bridge converted a multiple core definition
   * to a multiple output definition; it now converts it to a list output.
   */
  public function testOutputSchemaUnchanged(): void {
    $core = new ContextDefinition('string', 'Tags', required: FALSE, multiple: TRUE, description: 'Some tags.', constraints: [
      'Count' => ['max' => 3],
      'Length' => ['min' => 3],
    ]);
    $list = OutputDefinition::fromContextDefinition($core, 'tags');
    $this->assertInstanceOf(ListOutputDefinition::class, $list);
    $multiple = new OutputDefinition('string', 'Tags', 'Some tags.', required: FALSE, multiple: TRUE, constraints: $core->getConstraints());
    $this->assertSameSchema($this->normalize($multiple), $this->normalize($list));

    // Without a description the bridge synthesizes one for the list only.
    $core = new EntityContextDefinition('node', 'Nodes', multiple: TRUE);
    $list = OutputDefinition::fromContextDefinition($core, 'nodes');
    $this->assertInstanceOf(ListOutputDefinition::class, $list);
    $multiple = new EntityOutputDefinition('node', 'Nodes', 'The Nodes output.', multiple: TRUE);
    $this->assertSameSchema($this->normalize($multiple), $this->normalize($list));
  }

  /**
   * Tests a converted entity list moves the handle note to its item.
   *
   * For an invoker taking entities as handles, a multiple entity definition
   * was described as an array stating the label and the handle note, with
   * plain string items. The handle transform is local to each definition,
   * so the list it converts to states only its label and description, and
   * its entity item becomes a handle string described by the note alone,
   * without a title. The note still appears once in the schema; moving it
   * is the one deliberate difference between the two schemas.
   */
  public function testEntityHandleNoteMovesToItem(): void {
    $invoker = new Invoker('tool_test', InvokerCapability::EntitiesAsHandles);
    $context = [
      'tool_id' => 'converted_tool',
      'tool_invoker' => $invoker,
      'tool_definition_name' => 'nodes',
      'tool_direction' => ToolDefinitionNormalizeDirection::Input,
    ];
    $multiple = new EntityInputDefinition('node', 'Nodes', 'Some nodes.', required: FALSE, multiple: TRUE);
    $expected = $this->normalize($multiple, $context);
    $this->assertSame('Nodes: Some nodes. (pass a handle from a previous tool call)', $expected['description']);
    $this->assertSame(['type' => 'string'], $expected['items']);
    $schema = $this->normalize(ListInputDefinition::fromMultiple($multiple), $context);
    $this->assertSame('Nodes: Some nodes.', $schema['description']);
    $this->assertSameSchema(['type' => 'string', 'description' => '(pass a handle from a previous tool call)'], $schema['items']);
    $expected['description'] = 'Nodes: Some nodes.';
    $expected['items']['description'] = '(pass a handle from a previous tool call)';
    $this->assertSameSchema($expected, $schema);

    $context['tool_direction'] = ToolDefinitionNormalizeDirection::Output;
    $multiple = new EntityOutputDefinition('node', 'Nodes', 'Some nodes.', required: FALSE, multiple: TRUE);
    $expected = $this->normalize($multiple, $context);
    $this->assertSame('Nodes: Some nodes. (returns a handle for use in subsequent tool calls)', $expected['description']);
    $this->assertSame(['type' => 'string'], $expected['items']);
    $list = OutputDefinition::fromContextDefinition(new EntityContextDefinition('node', 'Nodes', required: FALSE, multiple: TRUE, description: 'Some nodes.'), 'nodes');
    $this->assertInstanceOf(ListOutputDefinition::class, $list);
    $schema = $this->normalize($list, $context);
    $this->assertSame('Nodes: Some nodes.', $schema['description']);
    $this->assertSameSchema(['type' => 'string', 'description' => '(returns a handle for use in subsequent tool calls)'], $schema['items']);
    $expected['description'] = 'Nodes: Some nodes.';
    $expected['items']['description'] = '(returns a handle for use in subsequent tool calls)';
    $this->assertSameSchema($expected, $schema);
  }

  /**
   * Normalizes a definition to JSON Schema through the serializer.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The definition.
   * @param array $context
   *   The normalizer context, such as the invoker a tool serializer passes.
   *
   * @return array
   *   The schema as a consumer decodes it.
   */
  protected function normalize(InputDefinitionInterface|OutputDefinitionInterface $definition, array $context = []): array {
    $schema = $this->container->get('serializer')->normalize($definition, 'json_schema', $context);
    return json_decode((string) json_encode($schema), TRUE);
  }

  /**
   * Asserts two schemas are identical apart from member order.
   *
   * @param array $expected
   *   The expected schema.
   * @param array $actual
   *   The actual schema.
   */
  protected function assertSameSchema(array $expected, array $actual): void {
    $this->assertSame($this->sortMembers($expected), $this->sortMembers($actual));
  }

  /**
   * Sorts the members of every object in a schema by name.
   *
   * @param array $schema
   *   The schema.
   *
   * @return array
   *   The schema with sorted object members; list order is kept.
   */
  protected function sortMembers(array $schema): array {
    foreach ($schema as $key => $member) {
      if (is_array($member)) {
        $schema[$key] = $this->sortMembers($member);
      }
    }
    if (!array_is_list($schema)) {
      ksort($schema);
    }
    return $schema;
  }

}
