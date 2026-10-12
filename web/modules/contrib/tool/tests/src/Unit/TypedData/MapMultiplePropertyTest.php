<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\TypedData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests a map converts a multiple property to a list (#3583049).
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedData\MapDefinitionBase
 */
class MapMultiplePropertyTest extends UnitTestCase {

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
   * Tests an input map converts a multiple property with a deprecation.
   *
   * The property converts the way a multiple input does, through
   * ListInputDefinition::fromMultiple(); every other property is kept.
   *
   * @covers ::setPropertyDefinitions
   */
  #[IgnoreDeprecations]
  public function testInputMapConvertsMultipleProperty(): void {
    $this->expectDeprecation("Declaring the 'tags' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $name = new InputDefinition('string', 'Name', 'N.');
    $map = new MapInputDefinition('Profile', 'P.', property_definitions: [
      'name' => $name,
      'tags' => new InputDefinition('string', 'Tags', 'T.', required: FALSE, multiple: TRUE, constraints: [
        'Count' => ['max' => 3],
        'Length' => ['min' => 3],
      ]),
    ]);
    $this->assertSame(['name', 'tags'], array_keys($map->getPropertyDefinitions()));
    $this->assertSame($name, $map->getPropertyDefinition('name'));

    $tags = $map->getPropertyDefinition('tags');
    $this->assertInstanceOf(ListInputDefinition::class, $tags);
    $this->assertTrue($tags->isConvertedFromMultiple());
    $this->assertFalse($tags->isMultiple());
    $this->assertFalse($tags->isRequired());
    $this->assertSame('Tags', $tags->getLabel());
    $this->assertSame(['Count' => ['max' => 3]], $tags->getConstraints());
    $item = $tags->getItemDefinition();
    $this->assertInstanceOf(InputDefinition::class, $item);
    $this->assertSame('string', $item->getDataType());
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
    $this->assertSame('', $item->getLabel());
    $this->assertTrue($item->isRequired());
  }

  /**
   * Tests an output map converts a multiple property with a deprecation.
   *
   * The property converts the way a multiple output does, through
   * ListOutputDefinition::fromMultiple().
   *
   * @covers ::setPropertyDefinitions
   */
  #[IgnoreDeprecations]
  public function testOutputMapConvertsMultipleProperty(): void {
    $this->expectDeprecation("Declaring the 'tags' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListOutputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $map = new MapOutputDefinition('Stats', 'S.', property_definitions: [
      'tags' => new OutputDefinition('string', 'Tags', 'T.', required: FALSE, multiple: TRUE, constraints: [
        'Count' => ['max' => 3],
        'Length' => ['min' => 3],
      ]),
    ]);
    $tags = $map->getPropertyDefinition('tags');
    $this->assertInstanceOf(ListOutputDefinition::class, $tags);
    $this->assertTrue($tags->isConvertedFromMultiple());
    $this->assertFalse($tags->isMultiple());
    $this->assertSame(['Count' => ['max' => 3]], $tags->getConstraints());
    $item = $tags->getItemDefinition();
    $this->assertInstanceOf(OutputDefinition::class, $item);
    $this->assertSame(['Length' => ['min' => 3]], $item->getConstraints());
  }

  /**
   * Tests a property is converted once.
   *
   * A converted list is not multiple, so setting the properties again or
   * cloning the map, alone or inside the list a multiple map converts to,
   * converts nothing and triggers no further deprecation. A list declared
   * as a list is never converted, even when marked multiple.
   *
   * @covers ::setPropertyDefinitions
   * @covers ::__clone
   */
  public function testConvertsOnce(): void {
    $deprecations = [];
    set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
      $deprecations[] = $message;
      return TRUE;
    }, E_USER_DEPRECATED);
    try {
      $map = new MapInputDefinition('Profile', 'P.', property_definitions: [
        'tags' => new InputDefinition('string', 'Tags', 'T.', multiple: TRUE),
      ]);
      $this->assertCount(1, $deprecations);
      $tags = $map->getPropertyDefinition('tags');
      $this->assertInstanceOf(ListInputDefinition::class, $tags);

      $map->setPropertyDefinitions($map->getPropertyDefinitions());
      $this->assertSame($tags, $map->getPropertyDefinition('tags'));

      $clone = clone $map;
      $clone->setPropertyDefinitions($clone->getPropertyDefinitions());
      $this->assertInstanceOf(ListInputDefinition::class, $clone->getPropertyDefinition('tags'));
      $this->assertNotSame($tags, $clone->getPropertyDefinition('tags'));

      $item = ListInputDefinition::fromMultiple((clone $map)->setMultiple())->getItemDefinition();
      $this->assertInstanceOf(MapInputDefinition::class, $item);
      $this->assertInstanceOf(ListInputDefinition::class, $item->getPropertyDefinition('tags'));

      $rows = (new ListInputDefinition('Rows', 'R.'))->setMultiple();
      $map->setPropertyDefinitions(['rows' => $rows]);
      $this->assertSame($rows, $map->getPropertyDefinition('rows'));
    }
    finally {
      restore_error_handler();
    }
    $this->assertCount(1, $deprecations);
  }

}
