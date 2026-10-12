<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Normalizer;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the schema of a map declared on a plain definition class.
 *
 * A definition with the map data type on InputDefinition or OutputDefinition
 * is not a MapDataDefinition. It must still be advertised as an object, the
 * same as the map classes advertise one, on every core: before 11.1 it came
 * out as a string (#3583071).
 *
 * On core 11.1 and later the branch this suite was written for also
 * produces an object without the fix, so only the Count-bounds cases can
 * fail here. The string regression itself is caught by the Drupal 10 CI
 * job, which is manual: run it on any change to this path.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class PlainMapSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'serialization', 'tool'];

  /**
   * Provides plain map definitions and the map class equivalent of each.
   *
   * @return \Generator
   *   Cases of [plain definition, map class definition, expected type].
   */
  public static function mapProvider(): \Generator {
    yield 'required input' => [
      new InputDefinition('map', 'Settings', 'The settings.'),
      new MapInputDefinition('Settings', 'The settings.'),
      'object',
    ];
    yield 'optional input' => [
      new InputDefinition('map', 'Settings', 'The settings.', FALSE),
      new MapInputDefinition('Settings', 'The settings.', FALSE),
      ['object', 'null'],
    ];
    yield 'required output' => [
      new OutputDefinition('map', 'Settings', 'The settings.'),
      new MapOutputDefinition('Settings', 'The settings.'),
      'object',
    ];
    yield 'optional output' => [
      new OutputDefinition('map', 'Settings', 'The settings.', FALSE),
      new MapOutputDefinition('Settings', 'The settings.', FALSE),
      ['object', 'null'],
    ];
    yield 'input with a count' => [
      new InputDefinition('map', 'Settings', 'The settings.', TRUE, FALSE, NULL, ['Count' => ['min' => 1, 'max' => 3]]),
      new MapInputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, ['Count' => ['min' => 1, 'max' => 3]]),
      'object',
    ];
  }

  /**
   * Tests a plain map is advertised the way the map classes advertise it.
   *
   * @param \Drupal\tool\TypedData\InputDefinition|\Drupal\tool\TypedData\OutputDefinition $plain
   *   The map declared on the plain definition class.
   * @param \Drupal\tool\TypedData\MapInputDefinition|\Drupal\tool\TypedData\MapOutputDefinition $map
   *   The same map declared on the map class.
   * @param string|string[] $type
   *   The expected JSON Schema type.
   *
   * @dataProvider mapProvider
   */
  #[DataProvider('mapProvider')]
  public function testPlainMapIsAnObject(InputDefinition|OutputDefinition $plain, MapInputDefinition|MapOutputDefinition $map, string|array $type): void {
    $serializer = $this->container->get('serializer');
    $schema = $serializer->normalize($plain, 'json_schema');

    $this->assertSame($type, $schema['type']);
    $this->assertEquals(new \ArrayObject(), $schema['properties']);
    $this->assertEquals($serializer->normalize($map, 'json_schema'), $schema);
  }

  /**
   * Tests a count on a plain map bounds its properties.
   */
  public function testPlainMapCountBoundsProperties(): void {
    $schema = $this->container->get('serializer')->normalize(
      new InputDefinition('map', 'Settings', 'The settings.', TRUE, FALSE, NULL, ['Count' => ['min' => 1, 'max' => 3]]),
      'json_schema',
    );
    $this->assertSame(1, $schema['minProperties']);
    $this->assertSame(3, $schema['maxProperties']);
  }

}
