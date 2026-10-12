<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Normalizer;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the tool module does not interfere with REST serialization.
 *
 * Regression test for the module's normalizers intercepting FieldItem
 * normalization for non-json_schema formats and swallowing field values.
 *
 * @see https://git.drupalcode.org/project/tool/-/work_items/3582939
 *
 * @group tool
 */
#[Group('tool')]
#[RunTestsInSeparateProcesses]
class RestSerializationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'entity_test',
    'serialization',
    'tool',
  ];

  /**
   * Tests entity field values survive 'json' normalization with tool enabled.
   */
  public function testEntityFieldsSurviveJsonNormalizationWithToolEnabled(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    $entity = EntityTest::create(['name' => 'Widget 42']);
    $entity->save();

    $normalized = $this->container->get('serializer')->normalize($entity, 'json');

    $this->assertSame('Widget 42', $normalized['name'][0]['value'] ?? NULL,
      'Field values must not be swallowed by tool module normalizers: ' . var_export($normalized['name'] ?? NULL, TRUE));
  }

}
