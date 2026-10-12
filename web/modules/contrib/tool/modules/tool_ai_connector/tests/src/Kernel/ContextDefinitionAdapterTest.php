<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool_ai_connector\Context\ToolContextDefinition;
use Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface;
use Drupal\tool_ai_connector\Context\ToolEntityContextDefinition;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the context definition adapters at the ai module boundary.
 *
 * The Tool API's input definitions are typed data (#3583016); the ai
 * module's function-call contract is typed on core context definitions.
 * The adapters carry the tool definition across that boundary (#3583018).
 *
 * @group tool_ai_connector
 */
#[Group('tool_ai_connector')]
#[RunTestsInSeparateProcesses]
final class ContextDefinitionAdapterTest extends KernelTestBase {

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
  ];

  /**
   * Tests the adapter copies the scalar facts of a tool definition.
   */
  public function testAdapterCopiesScalarFacts(): void {
    $definition = new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Count'),
      description: new TranslatableMarkup('How many.'),
      required: FALSE,
      multiple: TRUE,
      default_value: 3,
      constraints: ['Range' => ['min' => 0]],
    );
    $adapted = ToolContextDefinition::fromToolDefinition($definition);

    $this->assertInstanceOf(ToolContextDefinition::class, $adapted);
    $this->assertSame('integer', $adapted->getDataType());
    $this->assertSame('Count', (string) $adapted->getLabel());
    $this->assertSame('How many.', (string) $adapted->getDescription());
    $this->assertFalse($adapted->isRequired());
    $this->assertTrue($adapted->isMultiple());
    $this->assertSame(3, $adapted->getDefaultValue());
    $this->assertArrayHasKey('Range', $adapted->getConstraints());
    $this->assertSame($definition, $adapted->getToolDefinition());
  }

  /**
   * Tests entity data type routing.
   *
   * A concrete entity type needs the entity-aware class - core's plain
   * ContextDefinition rejects it - while a bare 'entity' input must stay
   * on the plain class: EntityContextDefinition would prefix it into the
   * non-existent 'entity:entity' data type.
   */
  public function testEntityDataTypeRouting(): void {
    $typed = ToolContextDefinition::fromToolDefinition(new EntityInputDefinition(
      'entity:user',
      new TranslatableMarkup('Account'),
      new TranslatableMarkup('The account.'),
    ));
    $this->assertInstanceOf(ToolEntityContextDefinition::class, $typed);
    $this->assertSame('entity:user', $typed->getDataType());

    $bare = ToolContextDefinition::fromToolDefinition(new InputDefinition(
      'entity',
      new TranslatableMarkup('Any entity'),
      new TranslatableMarkup('Any entity input.'),
    ));
    $this->assertInstanceOf(ToolContextDefinition::class, $bare);
    $this->assertSame('entity', $bare->getDataType());
  }

  /**
   * Tests a converted multiple input adapts as core's multiple.
   *
   * A multiple input is converted to a list when it is declared (#3583049),
   * and the ai module boundary is the one place core's multiple flag still
   * exists: the adapter presents the list as the multiple definition it
   * was, with its item's data type, and maps its state back onto the list.
   */
  public function testConvertedMultipleAdaptsAsCoreMultiple(): void {
    $list = ListInputDefinition::fromMultiple(new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('Some tags.'),
      required: FALSE,
      multiple: TRUE,
      constraints: ['Count' => ['max' => 3], 'Length' => ['min' => 2]],
    ));
    $adapted = ToolContextDefinition::fromToolDefinition($list);

    $this->assertInstanceOf(ToolContextDefinition::class, $adapted);
    $this->assertSame('string', $adapted->getDataType());
    $this->assertTrue($adapted->isMultiple());
    $this->assertFalse($adapted->isRequired());
    $this->assertSame('Tags', (string) $adapted->getLabel());
    $this->assertSame('Some tags.', (string) $adapted->getDescription());
    $this->assertEqualsCanonicalizing(['Count', 'Length'], array_keys($adapted->getConstraints()));
    $this->assertSame($list, $adapted->getToolDefinition());

    // An unchanged adapter maps back onto the list it wraps.
    $converted = $adapted->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame($list->getConstraints(), $converted->getConstraints());
    $this->assertSame($list->getItemDefinition()->getConstraints(), $converted->getItemDefinition()->getConstraints());

    // A constraint added to a clone binds each item, while Count stays on
    // the list, as they did on a multiple definition.
    $override = clone $adapted;
    $override->addConstraint('Choice', ['ab', 'cd']);
    $override->setRequired(TRUE);
    $converted = $override->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertTrue($converted->isRequired());
    $this->assertSame(['Count'], array_keys($converted->getConstraints()));
    $this->assertSame(['ab', 'cd'], $converted->getItemDefinition()->getConstraints()['Choice']);
    $this->assertArrayNotHasKey('Choice', $list->getItemDefinition()->getConstraints(), 'The wrapped list is untouched.');
    $this->assertInstanceOf(ListDataDefinitionInterface::class, $override->getDataDefinition(), 'The runtime definition is the list.');

    // A NotBlank added to a clone binds each item, exactly as a NotBlank
    // declared on the multiple input does; the list itself gains nothing.
    $override = clone $adapted;
    $override->addConstraint('NotBlank', []);
    $converted = $override->toToolDefinition();
    $not_blank = ListInputDefinition::fromMultiple(new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('Some tags.'),
      required: FALSE,
      multiple: TRUE,
      constraints: ['Count' => ['max' => 3], 'Length' => ['min' => 2], 'NotBlank' => []],
    ));
    $this->assertSame(['Count' => ['max' => 3]], $not_blank->getConstraints());
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame($not_blank->getConstraints(), $converted->getConstraints());
    $this->assertSame($not_blank->getItemDefinition()->getConstraints(), $converted->getItemDefinition()->getConstraints());

    // That list round-trips unchanged, and removing the NotBlank strips
    // it from the item; the list never carried one.
    $override = ToolContextDefinition::fromToolDefinition($not_blank);
    $converted = $override->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame($not_blank->getConstraints(), $converted->getConstraints());
    $this->assertSame($not_blank->getItemDefinition()->getConstraints(), $converted->getItemDefinition()->getConstraints());
    $override->setConstraints(array_diff_key($override->getConstraints(), ['NotBlank' => TRUE]));
    $converted = $override->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame($list->getConstraints(), $converted->getConstraints());
    $this->assertSame($list->getItemDefinition()->getConstraints(), $converted->getItemDefinition()->getConstraints());

    // Entity items route as a single entity definition does.
    $entities = ToolContextDefinition::fromToolDefinition(ListInputDefinition::fromMultiple(new EntityInputDefinition(
      data_type: 'entity:user',
      label: new TranslatableMarkup('Accounts'),
      description: new TranslatableMarkup('Some accounts.'),
      multiple: TRUE,
    )));
    $this->assertInstanceOf(ToolEntityContextDefinition::class, $entities);
    $this->assertSame('entity:user', $entities->getDataType());
    $this->assertTrue($entities->isMultiple());
    $this->assertInstanceOf(ListInputDefinition::class, $entities->toToolDefinition());

    $bare = ToolContextDefinition::fromToolDefinition(ListInputDefinition::fromMultiple(new InputDefinition(
      data_type: 'entity',
      label: new TranslatableMarkup('Any entities'),
      description: new TranslatableMarkup('Any entity inputs.'),
      multiple: TRUE,
    )));
    $this->assertInstanceOf(ToolContextDefinition::class, $bare);
    $this->assertSame('entity', $bare->getDataType());
    $this->assertTrue($bare->isMultiple());
  }

  /**
   * Tests an unchanged adapter maps back onto a declared list as it was.
   *
   * The item of a list declared as a list may carry NotBlank while the list
   * carries no NotNull. The adapter presents the item's constraints as those
   * of a multiple definition; a constraint it passes through unchanged maps
   * back onto the item alone, so neither the list nor its schema changes.
   * Only a constraint the adapter adds is placed the way a multiple
   * definition placed it.
   */
  public function testDeclaredListAdapterRoundTrips(): void {
    $list = new ListInputDefinition(
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('Some tags.'),
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'string',
        label: new TranslatableMarkup('Tag'),
        description: new TranslatableMarkup('A tag.'),
        constraints: ['NotBlank' => []],
      ),
    );
    $adapted = ToolContextDefinition::fromToolDefinition($list);
    $this->assertSame(['NotBlank' => []], $adapted->getConstraints());

    $converted = (clone $adapted)->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame($list->getConstraints(), $converted->getConstraints());
    $this->assertSame($list->getItemDefinition()->getConstraints(), $converted->getItemDefinition()->getConstraints());
    $this->assertSame($this->normalizeSchema($list), $this->normalizeSchema($converted));

    // A constraint added next to it binds each item and leaves the list as
    // it was.
    $override = clone $adapted;
    $override->addConstraint('Choice', ['ab', 'cd']);
    $converted = $override->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame([], $converted->getConstraints());
    $this->assertSame(['NotBlank' => [], 'Choice' => ['ab', 'cd']], $converted->getItemDefinition()->getConstraints());

    // A removed constraint is removed from the item.
    $override = clone $adapted;
    $override->setConstraints([]);
    $converted = $override->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame([], $converted->getConstraints());
    $this->assertSame([], $converted->getItemDefinition()->getConstraints());
  }

  /**
   * Tests an author-declared list NotBlank survives an override withdrawal.
   *
   * Only Count is ever presented for the list, so removing the item's
   * NotBlank withdraws nothing from the list, and the author's own
   * declaration stays, options intact (#3583069).
   */
  public function testDeclaredListNotBlankSurvivesOverride(): void {
    $list = new ListInputDefinition(
      label: new TranslatableMarkup('Tags'),
      description: new TranslatableMarkup('Some tags.'),
      required: FALSE,
      constraints: ['NotBlank' => ['message' => 'Pick at least one tag.']],
      item_definition: new InputDefinition(
        data_type: 'string',
        label: new TranslatableMarkup('Tag'),
        description: new TranslatableMarkup('A tag.'),
        constraints: ['NotBlank' => []],
      ),
    );
    $override = clone ToolContextDefinition::fromToolDefinition($list);
    // Removing the item's NotBlank withdraws nothing from the list: only
    // Count is ever presented for it, and the list-level NotBlank is the
    // author's own declaration.
    $override->setConstraints(array_diff_key($override->getConstraints(), ['NotBlank' => TRUE]));
    $converted = $override->toToolDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $converted);
    $this->assertSame(['NotBlank' => ['message' => 'Pick at least one tag.']], $converted->getConstraints());
    $this->assertArrayNotHasKey('NotBlank', $converted->getItemDefinition()->getConstraints());
  }

  /**
   * Tests adapter mutations flow into its one authoritative conversion.
   *
   * The ai module clones adapters and mutates them to override inputs;
   * toToolDefinition() must reflect the complete mutable state, and the
   * runtime data definition derives from the same result.
   */
  public function testAdapterMutationsFlowToToolDefinition(): void {
    $original = new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('The name.'),
      constraints: ['Length' => ['max' => 3]],
    );
    $adapted = ToolContextDefinition::fromToolDefinition($original);
    $adapted->setRequired(FALSE);
    $adapted->setLabel('Overridden');
    $adapted->setConstraints([]);

    $converted = $adapted->toToolDefinition();
    $this->assertFalse($converted->isRequired());
    $this->assertSame('Overridden', (string) $converted->getLabel());
    $this->assertSame([], $converted->getConstraints(), 'A removed constraint stays removed.');
    $this->assertTrue($original->isRequired(), 'The wrapped definition is untouched.');

    $data_definition = $adapted->getDataDefinition();
    $this->assertFalse($data_definition->isRequired(), 'The runtime definition reads the same interpretation.');
    $this->assertNull($data_definition->getConstraint('Length'), 'The removed constraint is absent from the runtime definition.');
  }

  /**
   * Tests a data type change on the adapter fails explicitly.
   */
  public function testAdapterDataTypeChangeFails(): void {
    $adapted = ToolContextDefinition::fromToolDefinition(new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('The name.'),
    ));
    $adapted->setDataType('integer');
    $this->expectException(\LogicException::class);
    $adapted->toToolDefinition();
  }

  /**
   * Tests a context minted on the adapter keeps map structure.
   */
  public function testAdapterDataDefinitionKeepsStructure(): void {
    $definition = new MapInputDefinition(
      label: new TranslatableMarkup('Profile'),
      description: new TranslatableMarkup('A profile map.'),
      required: FALSE,
      property_definitions: [
        'name' => new InputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Name'),
          description: new TranslatableMarkup('The name.'),
          required: FALSE,
        ),
      ],
    );
    $adapted = ToolContextDefinition::fromToolDefinition($definition);

    $data_definition = $adapted->getDataDefinition();
    $this->assertInstanceOf(MapDataDefinition::class, $data_definition);
    $this->assertArrayHasKey('name', $data_definition->getPropertyDefinitions());

    $context = new Context($adapted, ['name' => 'Ada']);
    $data = $context->getContextData();
    $this->assertInstanceOf(ComplexDataInterface::class, $data);
    $this->assertSame('Ada', $data->get('name')->getValue());
  }

  /**
   * Tests the function call exposes adapters, not raw tool definitions.
   */
  public function testFunctionCallContextDefinitionsAreAdapters(): void {
    $function_call = $this->container->get('plugin.manager.ai.function_calls')
      ->createInstance('tool:numeric_input_tool');

    $definitions = $function_call->getContextDefinitions();
    $this->assertArrayHasKey('number', $definitions);
    $this->assertInstanceOf(ToolContextDefinitionInterface::class, $definitions['number']);
    $this->assertInstanceOf(InputDefinition::class, $definitions['number']->getToolDefinition());
    $this->assertInstanceOf(ToolContextDefinitionInterface::class, $function_call->getContextDefinition('number'));
  }

  /**
   * Normalizes a definition to JSON Schema the way a consumer decodes it.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The definition.
   *
   * @return array
   *   The decoded schema.
   */
  protected function normalizeSchema(InputDefinitionInterface $definition): array {
    $schema = $this->container->get('serializer')->normalize($definition, 'json_schema');
    return json_decode((string) json_encode($schema), TRUE);
  }

}
