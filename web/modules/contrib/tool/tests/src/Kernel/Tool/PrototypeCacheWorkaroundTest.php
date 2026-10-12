<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

/**
 * Tests the TypedDataManager prototype cache collision workaround.
 *
 * Verifies that tool input/output validation is not affected by Drupal core's
 * TypedDataManager prototype cache bug, where Map properties with the same
 * name but different types/constraints collide in the cache. Input
 * validation walks structures itself, but stored output values validate
 * through core typed data, whose lazily created children go through the
 * colliding cache - the workaround resets it before every structured
 * validation.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedInputsTrait
 *
 * @see https://www.drupal.org/project/drupal/issues/3616328
 */
#[RunTestsInSeparateProcesses]
class PrototypeCacheWorkaroundTest extends KernelTestBase {

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
   * Pollutes the prototype cache with a Map property of a given type.
   *
   * @param string $property_name
   *   The property name to cache.
   * @param \Drupal\Core\TypedData\DataDefinition|\Drupal\Core\TypedData\MapDataDefinition $property_definition
   *   The definition for the property.
   * @param mixed $value
   *   The value to set for the property.
   */
  private function pollutePrototypeCache(string $property_name, $property_definition, mixed $value): void {
    $typed_data_manager = \Drupal::typedDataManager();
    $polluting_map = MapDataDefinition::create('map');
    $polluting_map->setPropertyDefinition($property_name, $property_definition);
    $instance = $typed_data_manager->create($polluting_map, [$property_name => $value]);
    assert($instance instanceof Map);
    $instance->get($property_name);
  }

  /**
   * Tests Map property type collision does not break validation.
   *
   * Simulates the scenario where entity field items cache a Map prototype
   * for a property name (e.g., "title" => Map), and then a tool's
   * MapInputDefinition defines the same property as a string.
   *
   * @covers ::validateInputValue
   */
  public function testMapPropertyTypeCollisionDoesNotBreakValidation(): void {
    // Cache "map::title" as a Map prototype.
    $this->pollutePrototypeCache('title', MapDataDefinition::create('map'), []);

    // Now validate a tool input where 'title' is a string.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new MapInputDefinition(
      label: 'Link Value',
      description: 'A map with a title string property.',
      required: FALSE,
      property_definitions: [
        'uri' => new InputDefinition(
          data_type: 'string',
          label: 'URI',
          description: 'The URI.',
          required: FALSE,
        ),
        'title' => new InputDefinition(
          data_type: 'string',
          label: 'Title',
          description: 'The link text.',
          required: FALSE,
        ),
      ],
    );

    $violations = $tool->validateInputValue(
      $definition,
      ['uri' => 'https://example.com', 'title' => 'Hello world'],
    );

    $this->assertCount(0, $violations, 'Map property type collision should not cause validation failure.');
  }

  /**
   * Tests scalar type collision does not return wrong typed data.
   *
   * When 'count' is cached as StringData, a later Map defining 'count' as
   * integer should still validate correctly.
   *
   * @covers ::validateInputValue
   */
  public function testScalarTypeCollisionDoesNotBreakValidation(): void {
    // Cache "map::count" as a StringData prototype.
    $this->pollutePrototypeCache('count', DataDefinition::create('string'), 'five');

    // Validate a map where 'count' should be an integer.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new MapInputDefinition(
      label: 'Stats',
      description: 'A map with an integer count.',
      required: FALSE,
      property_definitions: [
        'count' => new InputDefinition(
          data_type: 'integer',
          label: 'Count',
          description: 'An integer count.',
          required: FALSE,
        ),
      ],
    );

    $violations = $tool->validateInputValue($definition, ['count' => 42]);
    $this->assertCount(0, $violations, 'Scalar type collision should not cause validation failure.');
  }

  /**
   * Tests constraints are not silently dropped due to cache collision.
   *
   * When a property is cached without constraints, a later Map with
   * constraints on the same property should still enforce them.
   *
   * @covers ::validateInputValue
   */
  public function testConstraintsNotDroppedByCache(): void {
    // Cache "map::email" as StringData without constraints.
    $this->pollutePrototypeCache('email', DataDefinition::create('string'), 'anything');

    // Validate a map where 'email' has an Email constraint.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new MapInputDefinition(
      label: 'Contact',
      description: 'A map with a constrained email.',
      required: FALSE,
      property_definitions: [
        'email' => new InputDefinition(
          data_type: 'string',
          label: 'Email',
          description: 'Must be a valid email.',
          required: FALSE,
          constraints: ['Email' => []],
        ),
      ],
    );

    $violations = $tool->validateInputValue($definition, ['email' => 'not-an-email']);
    $this->assertGreaterThan(0, $violations->count(), 'Email constraint should not be silently dropped by the prototype cache.');
  }

  /**
   * Tests constraints do not leak from cache to unrelated Maps.
   *
   * When a property is cached WITH constraints, a later Map without
   * constraints on the same property should not inherit them.
   *
   * @covers ::validateInputValue
   */
  public function testConstraintsNotLeakedByCache(): void {
    // Cache "map::name" as StringData WITH a Length(max: 5) constraint.
    $name_def = DataDefinition::create('string');
    $name_def->addConstraint('Length', ['max' => 5]);
    $this->pollutePrototypeCache('name', $name_def, 'Hi');

    // Validate a map where 'name' has NO constraints.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new MapInputDefinition(
      label: 'Profile',
      description: 'A map with an unconstrained name.',
      required: FALSE,
      property_definitions: [
        'name' => new InputDefinition(
          data_type: 'string',
          label: 'Name',
          description: 'A name with no length limit.',
          required: FALSE,
        ),
      ],
    );

    $violations = $tool->validateInputValue(
      $definition,
      ['name' => 'A perfectly valid long name'],
    );
    $this->assertCount(0, $violations, 'Length constraint should not leak from the prototype cache to an unconstrained property.');
  }

  /**
   * Tests list item constraints are not dropped by cache collision.
   *
   * @covers ::validateInputValue
   */
  public function testListItemConstraintsNotDroppedByCache(): void {
    $typed_data_manager = \Drupal::typedDataManager();

    // Pollute: list of unconstrained strings.
    $polluting_list = ListDataDefinition::create('string');
    $list_instance = $typed_data_manager->create($polluting_list, ['anything']);
    // Iterate to trigger prototype caching of the item.
    iterator_to_array($list_instance);

    // Validate a list with Length(max: 3) constraint on items.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new ListInputDefinition(
      label: 'Short Tags',
      description: 'Tags with max 3 chars.',
      required: FALSE,
      item_definition: new InputDefinition(
        data_type: 'string',
        label: 'Tag',
        description: 'A short tag.',
        constraints: ['Length' => ['max' => 3]],
      ),
    );

    $violations = $tool->validateInputValue($definition, ['exceeds max']);
    $this->assertGreaterThan(0, $violations->count(), 'List item constraint should not be silently dropped by the prototype cache.');
  }

  /**
   * Tests list constraints are not validated against a polluted prototype.
   *
   * A list declaring its own constraints validates through core typed data,
   * whose rebuilt list holds 'any' items. An earlier list of 'any' items in
   * the same request caches a prototype for them, so list constraint
   * validation must not read a polluted item prototype (core #3616328).
   *
   * @covers ::validateInputValue
   * @covers ::validateDeclaredConstraints
   */
  public function testListConstraintsNotPollutedByItemPrototype(): void {
    $typed_data_manager = \Drupal::typedDataManager();

    // Pollute: list of 'any' items with a Length(max: 1) constraint.
    $polluting_list = ListDataDefinition::createFromItemType('any');
    $polluting_item = $polluting_list->getItemDefinition();
    assert($polluting_item instanceof DataDefinition);
    $polluting_item->addConstraint('Length', ['max' => 1]);
    $typed_data_manager->create($polluting_list, ['zz']);

    // Validate a list with Count(min: 2) and Length(min: 3) on its items.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new ListInputDefinition(
      label: 'Codes',
      description: 'A count-constrained list of strings.',
      required: FALSE,
      constraints: ['Count' => ['min' => 2]],
      item_definition: new InputDefinition(
        data_type: 'string',
        label: 'Code',
        description: 'A length-constrained string item.',
        constraints: ['Length' => ['min' => 3]],
      ),
    );

    $violations = $tool->validateInputValue($definition, ['alpha', 'beta']);
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * Tests map constraints read the declared property, not a cached one.
   *
   * A constraint declared on a map reads the properties it names, which
   * core creates through the prototype cache. An earlier map caching a
   * same-named property of another type must not stand in for the declared
   * one: a nested map read as a string cannot hold its own ComplexData
   * (core #3616328).
   *
   * @covers ::validateInputValue
   * @covers ::validateDeclaredConstraints
   */
  public function testMapConstraintsNotPollutedByPropertyPrototype(): void {
    // Cache "map::settings" as a StringData prototype.
    $this->pollutePrototypeCache('settings', DataDefinition::create('string'), 'plain');

    // Validate a map whose nested settings map is constrained.
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = new MapInputDefinition(
      label: 'Display',
      description: 'A map with constrained nested settings.',
      required: FALSE,
      constraints: ['ComplexData' => ['settings' => ['ComplexData' => ['mode' => ['Length' => ['min' => 3]]]]]],
      property_definitions: [
        'settings' => new MapInputDefinition(
          label: 'Settings',
          description: 'A nested map.',
          required: FALSE,
          property_definitions: [
            'mode' => new InputDefinition(
              data_type: 'string',
              label: 'Mode',
              description: 'A mode name.',
              required: FALSE,
            ),
          ],
        ),
      ],
    );

    $violations = $tool->validateInputValue($definition, ['settings' => ['mode' => 'x']]);
    $this->assertCount(1, $violations, (string) $violations);
    $this->assertSame('settings.mode', $violations->get(0)->getPropertyPath());
  }

  /**
   * Tests same-named map output properties do not cross-contaminate.
   *
   * Stored output values validate through core typed data, whose map
   * properties are instantiated through the prototype cache. Two outputs
   * declaring a same-named property with different types and constraints
   * must each validate against their own declaration.
   *
   * @covers \Drupal\tool\TypedOutputsTrait::validateOutputs
   */
  public function testOutputMapPropertyCollision(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $definition = $tool->getPluginDefinition();
    $this->assertInstanceOf(ToolDefinition::class, $definition);
    $definition->addOutputDefinition('first', new MapOutputDefinition(
      label: new TranslatableMarkup('First'),
      description: new TranslatableMarkup('A map whose value is an integer.'),
      required: FALSE,
      property_definitions: [
        'value' => new OutputDefinition(
          data_type: 'integer',
          label: new TranslatableMarkup('Value'),
          description: new TranslatableMarkup('An integer value.'),
          required: FALSE,
        ),
      ],
    ));
    $definition->addOutputDefinition('second', new MapOutputDefinition(
      label: new TranslatableMarkup('Second'),
      description: new TranslatableMarkup('A map whose value is a short string.'),
      required: FALSE,
      property_definitions: [
        'value' => new OutputDefinition(
          data_type: 'string',
          label: new TranslatableMarkup('Value'),
          description: new TranslatableMarkup('A short string value.'),
          required: FALSE,
          constraints: ['Length' => ['max' => 3]],
        ),
      ],
    ));

    $tool->setOutputValue('first', ['value' => 1]);
    $tool->setOutputValue('second', ['value' => 'overlong']);

    $violations = $tool->validateOutputs();
    $this->assertCount(1, $violations);
    $message = (string) $violations->get(0)->getMessage();
    $this->assertStringNotContainsString('primitive type', $message);
    $this->assertStringContainsString('characters or less', $message);
  }

}
