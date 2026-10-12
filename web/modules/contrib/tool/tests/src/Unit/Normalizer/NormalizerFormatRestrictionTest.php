<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\Normalizer;

use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\Normalizer\ContextDefinitionNormalizer;
use Drupal\tool\Normalizer\MapDefinitionNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests that normalizers restrict themselves to the json_schema format.
 *
 * MapDefinitionNormalizer and ContextDefinitionNormalizer must return an empty
 * array from getSupportedTypes() for any format other than json_schema.
 * Returning FALSE for a class causes Symfony to fall back to
 * supportsNormalization(), which would let these normalizers intercept REST and
 * JSON:API requests and produce NULL field values.
 *
 * @see https://git.drupalcode.org/project/tool/-/work_items/3582939
 *
 * @group tool
 */
#[CoversClass(MapDefinitionNormalizer::class)]
#[CoversClass(ContextDefinitionNormalizer::class)]
#[Group('tool')]
class NormalizerFormatRestrictionTest extends UnitTestCase {

  /**
   * Creates a ContextDefinitionNormalizer with a mocked event dispatcher.
   */
  protected function createContextDefinitionNormalizer(): ContextDefinitionNormalizer {
    return new ContextDefinitionNormalizer($this->createMock(EventDispatcherInterface::class));
  }

  /**
   * Tests MapDefinitionNormalizer rejects non-json_schema formats.
   *
   * @dataProvider nonJsonSchemaFormats
   */
  #[DataProvider('nonJsonSchemaFormats')]
  public function testMapDefinitionNormalizerRejectsNonJsonSchemaFormats(?string $format): void {
    $normalizer = new MapDefinitionNormalizer();
    $this->assertSame([], $normalizer->getSupportedTypes($format));
  }

  /**
   * Tests MapDefinitionNormalizer supports the json_schema format.
   */
  public function testMapDefinitionNormalizerAcceptsJsonSchema(): void {
    $normalizer = new MapDefinitionNormalizer();
    $supported = $normalizer->getSupportedTypes('json_schema');
    $this->assertArrayHasKey(Map::class, $supported);
    $this->assertTrue($supported[Map::class]);
  }

  /**
   * Tests ContextDefinitionNormalizer rejects non-json_schema formats.
   *
   * @dataProvider nonJsonSchemaFormats
   */
  #[DataProvider('nonJsonSchemaFormats')]
  public function testContextDefinitionNormalizerRejectsNonJsonSchemaFormats(?string $format): void {
    $normalizer = $this->createContextDefinitionNormalizer();
    $this->assertSame([], $normalizer->getSupportedTypes($format));
  }

  /**
   * Tests ContextDefinitionNormalizer supports the json_schema format.
   */
  public function testContextDefinitionNormalizerAcceptsJsonSchema(): void {
    $normalizer = $this->createContextDefinitionNormalizer();
    $supported = $normalizer->getSupportedTypes('json_schema');
    $this->assertArrayHasKey(ContextDefinitionInterface::class, $supported);
    $this->assertTrue($supported[ContextDefinitionInterface::class]);
  }

  /**
   * Formats that should be rejected by these normalizers.
   */
  public static function nonJsonSchemaFormats(): array {
    return [
      'json' => ['json'],
      'hal_json' => ['hal_json'],
      'xml' => ['xml'],
      'jsonapi' => ['jsonapi'],
      'null format' => [NULL],
    ];
  }

}
