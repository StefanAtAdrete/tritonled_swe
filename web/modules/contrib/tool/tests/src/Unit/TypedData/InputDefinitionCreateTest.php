<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\TypedData;

use Drupal\Tests\UnitTestCase;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;

/**
 * Tests the InputDefinition static factories (#3582962).
 *
 * The inherited core factory either failed on the required label and
 * description or returned a core EntityContextDefinition that does not
 * implement InputDefinitionInterface.
 *
 * @group tool
 */
class InputDefinitionCreateTest extends UnitTestCase {

  /**
   * Tests creating a scalar input definition.
   *
   * @covers \Drupal\tool\TypedData\InputDefinition::create
   */
  public function testCreateScalar(): void {
    $definition = InputDefinition::create('string');
    $this->assertInstanceOf(InputDefinition::class, $definition);
    $this->assertSame('string', $definition->getDataType());
  }

  /**
   * Tests the entity branch returns an input definition.
   *
   * @covers \Drupal\tool\TypedData\InputDefinition::create
   */
  public function testCreateEntityReturnsInputDefinition(): void {
    $definition = InputDefinition::create('entity:node');
    $this->assertInstanceOf(EntityInputDefinition::class, $definition);
    $this->assertInstanceOf(InputDefinitionInterface::class, $definition);
    $this->assertSame('entity:node', $definition->getDataType());
  }

  /**
   * Tests the entity factory accepts both prefixed and bare forms.
   *
   * @covers \Drupal\tool\TypedData\EntityInputDefinition::create
   */
  public function testEntityCreateNormalizesPrefix(): void {
    $this->assertSame('entity:node', EntityInputDefinition::create('node')->getDataType());
    $this->assertSame('entity:node', EntityInputDefinition::create('entity:node')->getDataType());
  }

  /**
   * Tests the structured definition factories construct their own class.
   *
   * @covers \Drupal\tool\TypedData\MapInputDefinition::create
   * @covers \Drupal\tool\TypedData\ListInputDefinition::create
   */
  public function testStructuredCreateFactories(): void {
    $map = MapInputDefinition::create();
    $this->assertInstanceOf(MapInputDefinition::class, $map);
    $this->assertSame('map', $map->getDataType());

    $list = ListInputDefinition::create();
    $this->assertInstanceOf(ListInputDefinition::class, $list);
    $this->assertSame('list', $list->getDataType());
  }

}
