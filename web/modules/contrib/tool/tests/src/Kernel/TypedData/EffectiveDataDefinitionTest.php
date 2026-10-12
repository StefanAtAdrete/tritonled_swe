<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\TypedData;

use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\TypedData\EffectiveDataDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the effective definition rebuild used for storage and validation.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class EffectiveDataDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'serialization', 'tool'];

  /**
   * The rebuilt data definition carries typed data's default constraints.
   *
   * Validation reads the constraints of the definition the stored typed
   * data was created from, so the defaults must be merged there while the
   * declared definition's getConstraints() stays author-only.
   */
  public function testDataDefinitionCarriesDefaults(): void {
    $definition = new InputDefinition('integer', 'Count', 'How many.', constraints: ['Range' => ['min' => 1]]);
    $this->assertSame(['Range' => ['min' => 1]], $definition->getConstraints());

    $data_definition = EffectiveDataDefinition::createFrom($definition);
    $this->assertNotSame($definition, $data_definition);
    $constraints = $data_definition->getConstraints();
    $this->assertArrayHasKey('PrimitiveType', $constraints);
    $this->assertArrayHasKey('NotNull', $constraints);
    $this->assertSame(['min' => 1], $constraints['Range']);
    $this->assertSame('Count', (string) $data_definition->getLabel());
    $this->assertTrue($data_definition->isRequired());
  }

  /**
   * A multiple definition rebuilds as a list of its type.
   */
  public function testMultipleWrapsInList(): void {
    $definition = new OutputDefinition('string', 'Tags', 'Tags.', multiple: TRUE);
    $data_definition = EffectiveDataDefinition::createFrom($definition);
    $this->assertInstanceOf(ListDataDefinitionInterface::class, $data_definition);
    $this->assertSame('string', $data_definition->getItemDefinition()->getDataType());
  }

  /**
   * Map properties reach the rebuilt map, each rebuilt in turn.
   */
  public function testMapPropertiesAreCopied(): void {
    $definition = new MapInputDefinition('Map', 'Map.', property_definitions: [
      'count' => new InputDefinition('integer', 'Count', 'Count.'),
    ]);
    $data_definition = EffectiveDataDefinition::createFrom($definition);
    $this->assertInstanceOf(MapDataDefinition::class, $data_definition);
    $count = $data_definition->getPropertyDefinition('count');
    $this->assertSame('integer', $count->getDataType());
    $this->assertArrayHasKey('NotNull', $count->getConstraints());

    $multiple = new MapInputDefinition('Maps', 'Maps.', multiple: TRUE, property_definitions: [
      'count' => new InputDefinition('integer', 'Count', 'Count.'),
    ]);
    $data_definition = EffectiveDataDefinition::createFrom($multiple);
    $this->assertInstanceOf(ListDataDefinitionInterface::class, $data_definition);
    $item = $data_definition->getItemDefinition();
    $this->assertInstanceOf(MapDataDefinition::class, $item);
    $this->assertSame('integer', $item->getPropertyDefinition('count')->getDataType());
  }

}
