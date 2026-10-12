<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\TypedData;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\TypedData\EntityDefinitionBase;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListDefinitionBase;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapDefinitionBase;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the typed-data parentage of the definition families.
 *
 * Step 1 of the context detach (#3583017): every class extends typed data,
 * still answers as a context definition, and keeps the state, cloning and
 * serialization behavior it had as a context definition.
 *
 * @group tool
 */
class DefinitionParentageTest extends UnitTestCase {

  /**
   * Every class extends its typed-data parent and left the context system.
   */
  public function testParentage(): void {
    $cases = [
      [new InputDefinition('string', 'L', 'D'), DataDefinition::class, InputDefinitionInterface::class],
      [new MapInputDefinition('L', 'D'), MapDataDefinition::class, InputDefinitionInterface::class],
      [new ListInputDefinition('L', 'D'), ListDataDefinition::class, InputDefinitionInterface::class],
      [new EntityInputDefinition('node', 'L', 'D'), EntityDataDefinition::class, InputDefinitionInterface::class],
      [new OutputDefinition('string', 'L', 'D'), DataDefinition::class, OutputDefinitionInterface::class],
      [new MapOutputDefinition('L', 'D'), MapDataDefinition::class, OutputDefinitionInterface::class],
      [new ListOutputDefinition('L', 'D'), ListDataDefinition::class, OutputDefinitionInterface::class],
      [new EntityOutputDefinition('node', 'L', 'D'), EntityDataDefinition::class, OutputDefinitionInterface::class],
    ];
    foreach ($cases as [$definition, $parent, $interface]) {
      $this->assertInstanceOf($parent, $definition);
      $this->assertInstanceOf(DataDefinitionInterface::class, $definition);
      $this->assertInstanceOf($interface, $definition);
      $this->assertNotInstanceOf(ContextDefinitionInterface::class, $definition);
    }
    // Each structured family shares one abstract base per typed-data parent,
    // so "one of our map definitions" has a name.
    $bases = [
      MapInputDefinition::class => MapDefinitionBase::class,
      MapOutputDefinition::class => MapDefinitionBase::class,
      ListInputDefinition::class => ListDefinitionBase::class,
      ListOutputDefinition::class => ListDefinitionBase::class,
      EntityInputDefinition::class => EntityDefinitionBase::class,
      EntityOutputDefinition::class => EntityDefinitionBase::class,
    ];
    foreach ($bases as $class => $base) {
      $this->assertSame($base, get_parent_class($class));
      $this->assertTrue((new \ReflectionClass($base))->isAbstract());
    }
  }

  /**
   * The constructor arguments come back exactly as declared.
   */
  public function testConstructorState(): void {
    $definition = new InputDefinition(
      'integer',
      'Count',
      'How many.',
      required: FALSE,
      multiple: TRUE,
      default_value: [3],
      constraints: ['Range' => ['min' => 0]],
      locked: TRUE,
      examples: [1, 2],
    );
    $this->assertSame('integer', $definition->getDataType());
    $this->assertSame('Count', $definition->getLabel());
    $this->assertSame('How many.', $definition->getDescription());
    $this->assertFalse($definition->isRequired());
    $this->assertTrue($definition->isMultiple());
    $this->assertSame([3], $definition->getDefaultValue());
    $this->assertSame(['Range' => ['min' => 0]], $definition->getConstraints());
    $this->assertTrue($definition->isLocked());
    $this->assertSame([1, 2], $definition->getExamples());

    $this->assertSame('entity:node', (new EntityInputDefinition('node', 'L', 'D'))->getDataType());
    $this->assertSame('entity:node', (new EntityOutputDefinition('entity:node', 'L', 'D'))->getDataType());
    $this->assertSame('node', (new EntityInputDefinition('node', 'L', 'D'))->getEntityTypeId());
    // A bundle-qualified type keeps the old substring result, and the
    // property walk that would make the entity type manager throw is closed.
    $article = new EntityInputDefinition('node:article', 'L', 'D');
    $this->assertSame('node:article', $article->getEntityTypeId());
    $this->assertSame([], $article->getPropertyDefinitions());
    $this->assertSame('list', (new ListInputDefinition('L', 'D'))->getDataType());
    $this->assertSame('map', (new MapOutputDefinition('L', 'D'))->getDataType());
  }

  /**
   * Declared constraints are returned without typed data's defaults.
   *
   * DataDefinition::getConstraints() would add PrimitiveType and NotNull to a
   * required integer. The normalizer reads getConstraints() to build the
   * published schema, so those must not appear. This is also what keeps this
   * test container-free: the parent's version needs the typed data manager.
   */
  public function testConstraintsAreAuthorOnly(): void {
    $definition = new InputDefinition('integer', 'L', 'D', constraints: ['Range' => ['min' => 1]]);
    $this->assertSame(['Range' => ['min' => 1]], $definition->getConstraints());
    $this->assertSame(['min' => 1], $definition->getConstraint('Range'));
    $this->assertNull($definition->getConstraint('NotNull'));
    $this->assertSame([], (new OutputDefinition('integer', 'L', 'D'))->getConstraints());
    $this->assertSame([], (new EntityInputDefinition('node', 'L', 'D'))->getConstraints());
  }

  /**
   * Cloning a map or list does not share its child definitions.
   */
  public function testCloneIsolatesChildren(): void {
    $map = new MapInputDefinition('L', 'D', property_definitions: ['a' => new InputDefinition('string', 'A', 'A.')]);
    $clone = clone $map;
    $cloned_property = $clone->getPropertyDefinition('a');
    $this->assertInstanceOf(InputDefinition::class, $cloned_property);
    $this->assertNotSame($map->getPropertyDefinition('a'), $cloned_property);
    $cloned_property->setLabel('changed');
    $this->assertSame('A', $map->getPropertyDefinition('a')->getLabel());

    $list = new ListOutputDefinition('L', 'D', item_definition: new OutputDefinition('string', 'I', 'I.'));
    $clone = clone $list;
    $this->assertNotSame($list->getItemDefinition(), $clone->getItemDefinition());

    // A list declared without an item definition must clone without error.
    $bare = clone new ListInputDefinition('L', 'D');
    $this->assertSame('any', $bare->getItemDefinition()->getDataType());
  }

  /**
   * Map properties and list items survive serialization.
   *
   * Plugin definitions travel through the discovery cache serialized, and
   * ComplexDataDefinitionBase::__sleep() drops property definitions.
   */
  public function testSerializationKeepsChildren(): void {
    $map = new MapInputDefinition('L', 'D', property_definitions: [
      'a' => new InputDefinition('string', 'A', 'A.'),
      'nested' => new MapInputDefinition('N', 'N.', property_definitions: ['b' => new InputDefinition('integer', 'B', 'B.')]),
    ]);
    $restored = unserialize(serialize($map), [
      'allowed_classes' => [MapInputDefinition::class, InputDefinition::class],
    ]);
    assert($restored instanceof MapInputDefinition);
    $this->assertSame(['a', 'nested'], array_keys($restored->getPropertyDefinitions()));
    $this->assertSame('string', $restored->getPropertyDefinition('a')->getDataType());
    $nested = $restored->getPropertyDefinition('nested');
    assert($nested instanceof MapInputDefinition);
    $this->assertSame(['b'], array_keys($nested->getPropertyDefinitions()));

    $list = new ListInputDefinition('L', 'D', default_value: [1], item_definition: new InputDefinition('integer', 'I', 'I.'));
    $restored = unserialize(serialize($list), [
      'allowed_classes' => [ListInputDefinition::class, InputDefinition::class],
    ]);
    assert($restored instanceof ListInputDefinition);
    $this->assertSame('integer', $restored->getItemDefinition()->getDataType());
    $this->assertSame([1], $restored->getDefaultValue());
  }

  /**
   * The list setter accepts input definitions and rejects everything else.
   */
  public function testListItemBoundary(): void {
    $list = new ListInputDefinition('L', 'D');
    $list->setItemDefinition(new InputDefinition('string', 'S', 'S.'));
    $this->assertSame('string', $list->getItemDefinition()->getDataType());
    $this->expectException(\InvalidArgumentException::class);
    $list->setItemDefinition(DataDefinition::create('string'));
  }

  /**
   * The list setter rejects core context definitions.
   *
   * They are no longer data definitions from the family's point of view,
   * so the parameter type itself enforces the boundary.
   */
  public function testListItemRejectsCoreContextDefinition(): void {
    $this->expectException(\TypeError::class);
    (new ListInputDefinition('L', 'D'))->setItemDefinition(new ContextDefinition('string', 'S'));
  }

  /**
   * The list setter rejects output definitions.
   *
   * They are data definitions, so the parameter type lets them through. An
   * accepted one would be ignored: getItemDefinition() falls back to an
   * untyped input item for anything that is not an input definition.
   */
  #[DataProvider('outputDefinitionProvider')]
  public function testListItemRejectsOutputDefinition(DataDefinitionInterface $definition): void {
    $list = new ListInputDefinition('L', 'D');
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The item definition must be an instance of InputDefinitionInterface.');
    $list->setItemDefinition($definition);
  }

  /**
   * The list constructor rejects an output item by its parameter type.
   */
  public function testListConstructorRejectsOutputItem(): void {
    $this->expectException(\TypeError::class);
    new ListInputDefinition('L', 'D', item_definition: new OutputDefinition('string', 'O', 'O.'));
  }

  /**
   * The map setters accept input definitions and reject everything else.
   */
  public function testMapPropertyBoundary(): void {
    $map = new MapInputDefinition('L', 'D', property_definitions: [
      'a' => new InputDefinition('string', 'A', 'A.'),
    ]);
    $map->setPropertyDefinitions(['b' => new InputDefinition('integer', 'B', 'B.')]);
    $map->setPropertyDefinition('c', new InputDefinition('boolean', 'C', 'C.'));
    $this->assertSame(['b', 'c'], array_keys($map->getPropertyDefinitions()));
    // Passing no definition still removes the property.
    $map->setPropertyDefinition('b');
    $this->assertSame(['c'], array_keys($map->getPropertyDefinitions()));
    $this->expectException(\InvalidArgumentException::class);
    $map->setPropertyDefinitions(['d' => DataDefinition::create('string')]);
  }

  /**
   * Tests the singular property setter converts a multiple property.
   *
   * The singular setter routes through setPropertyDefinitions(), so a
   * multiple property added on its own gets the same #3583049 conversion
   * and deprecation as a full set of properties.
   */
  #[IgnoreDeprecations]
  public function testMapSinglePropertySetterConvertsMultiple(): void {
    $this->expectUserDeprecationMessage("Declaring the 'tags' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $map = new MapInputDefinition(label: 'Settings', description: 'The settings.');
    $map->setPropertyDefinition('tags', new InputDefinition(
      data_type: 'string',
      label: 'Tags',
      description: 'The tags.',
      multiple: TRUE,
    ));
    $this->assertInstanceOf(ListInputDefinition::class, $map->getPropertyDefinitions()['tags']);
  }

  /**
   * The map setter rejects core context definitions.
   *
   * The setter takes an array, so its own check rejects them rather than a
   * parameter type.
   */
  public function testMapPropertyRejectsCoreContextDefinition(): void {
    $this->expectException(\InvalidArgumentException::class);
    (new MapInputDefinition('L', 'D'))->setPropertyDefinitions([
      'a' => new ContextDefinition('string', 'S'),
    ]);
  }

  /**
   * The single-property setter rejects core context definitions.
   *
   * Its parameter type enforces the boundary, as the list setter's does.
   */
  public function testMapSinglePropertyRejectsCoreContextDefinition(): void {
    $this->expectException(\TypeError::class);
    (new MapInputDefinition('L', 'D'))->setPropertyDefinition('a', new ContextDefinition('string', 'S'));
  }

  /**
   * The map constructor rejects output definitions as properties.
   */
  #[DataProvider('outputDefinitionProvider')]
  public function testMapConstructorRejectsOutputProperty(DataDefinitionInterface $definition): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Property definitions must be instances of InputDefinitionInterface.');
    new MapInputDefinition('L', 'D', property_definitions: ['a' => $definition]);
  }

  /**
   * The map setter rejects output definitions.
   *
   * An accepted one would be ignored: input validation skips map properties
   * that are not input definitions.
   */
  #[DataProvider('outputDefinitionProvider')]
  public function testMapPropertyRejectsOutputDefinition(DataDefinitionInterface $definition): void {
    $map = new MapInputDefinition('L', 'D');
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Property definitions must be instances of InputDefinitionInterface.');
    $map->setPropertyDefinitions([
      'a' => new InputDefinition('string', 'A', 'A.'),
      'b' => $definition,
    ]);
  }

  /**
   * The single-property setter enforces the same boundary as the full set.
   */
  #[DataProvider('outputDefinitionProvider')]
  public function testMapSinglePropertyRejectsOutputDefinition(DataDefinitionInterface $definition): void {
    $map = new MapInputDefinition('L', 'D');
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Property definitions must be instances of InputDefinitionInterface.');
    $map->setPropertyDefinition('a', $definition);
  }

  /**
   * Provides one definition of each output class.
   */
  public static function outputDefinitionProvider(): array {
    return [
      'scalar' => [new OutputDefinition('string', 'O', 'O.')],
      'map' => [new MapOutputDefinition('O', 'O.')],
      'list' => [new ListOutputDefinition('O', 'O.')],
      'entity' => [new EntityOutputDefinition('node', 'O', 'O.')],
    ];
  }

}
