<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool_ai_connector\SchemaToolsPropertyConverter;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests JSON schema conversion to AI tool properties.
 *
 * @group tool_ai_connector
 *
 * @covers \Drupal\tool_ai_connector\SchemaToolsPropertyConverter
 */
#[RunTestsInSeparateProcesses]
final class SchemaToolsPropertyConverterTest extends KernelTestBase {

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
    'tool_ai_connector',
  ];

  /**
   * Tests nested properties and required children are preserved.
   */
  public function testConvertsNestedRequiredProperties(): void {
    $property = $this->getConverter()->convert('payload:input', [
      'description' => 'Payload.',
      'properties' => [
        'count' => [
          'type' => 'integer',
          'description' => 'A count.',
        ],
        'metadata' => [
          'properties' => [
            'label' => [
              'type' => 'string',
              'description' => 'A label.',
            ],
          ],
          'required' => ['label'],
        ],
      ],
      'required' => ['count', 'metadata'],
    ], TRUE);

    $this->assertTrue($property->isRequired());
    $rendered = $property->renderPropertyArray();
    $this->assertSame('payload__colon__input', $rendered['name']);
    $this->assertSame('object', $rendered['type']);
    $this->assertSame('Payload.', $rendered['description']);
    $this->assertSame(['count', 'metadata'], $rendered['required']);
    $this->assertSame('integer', $rendered['properties']['count']['type']);
    $this->assertSame('A count.', $rendered['properties']['count']['description']);
    $this->assertSame('object', $rendered['properties']['metadata']['type']);
    $this->assertSame(['label'], $rendered['properties']['metadata']['required']);
    $this->assertSame('string', $rendered['properties']['metadata']['properties']['label']['type']);
    $this->assertSame('A label.', $rendered['properties']['metadata']['properties']['label']['description']);
  }

  /**
   * Tests schema composition is exposed as an AI-compatible type value.
   */
  public function testConvertsCompositionSchemaToAiType(): void {
    // A nullable union of bare types flattens to the non-null type — a
    // rendered type object under 'type' is invalid JSON Schema draft 2020-12
    // and rejected by strict providers (e.g. Anthropic).
    $property = $this->getConverter()->convert('value', [
      'anyOf' => [
        ['type' => 'string'],
        ['type' => 'null'],
      ],
    ]);
    $rendered = $property->renderPropertyArray();
    $this->assertSame('value', $rendered['name']);
    $this->assertSame('string', $rendered['type']);

    // Same for oneOf (core's nullable normalization shape).
    $property = $this->getConverter()->convert('value', [
      'oneOf' => [
        ['type' => 'integer'],
        ['type' => 'null'],
      ],
    ]);
    $this->assertSame('integer', $property->renderPropertyArray()['type']);

    // Compositions that are not bare nullable type unions keep the existing
    // type-composition passthrough.
    $property = $this->getConverter()->convert('value', [
      'anyOf' => [
        ['type' => 'string', 'format' => 'email'],
        ['type' => 'string', 'format' => 'uri'],
      ],
    ]);
    $this->assertSame([
      'anyOf' => [
        ['type' => 'string', 'format' => 'email'],
        ['type' => 'string', 'format' => 'uri'],
      ],
    ], $property->renderPropertyArray()['type']);
  }

  /**
   * Tests nullable type arrays flatten to the non-null type.
   */
  public function testFlattensNullableTypeArrays(): void {
    $property = $this->getConverter()->convert('fields', [
      'type' => ['array', 'null'],
      'items' => ['type' => 'string'],
      'description' => 'Array of fields.',
    ]);

    $rendered = $property->renderPropertyArray();
    $this->assertSame('array', $rendered['type']);
    $this->assertSame(['type' => 'string'], $rendered['items']);

    $property = $this->getConverter()->convert('value', [
      'type' => ['object', 'null'],
    ]);
    $this->assertSame('object', $property->renderPropertyArray()['type']);
  }

  /**
   * Tests a null is dropped from the enum of an optional value.
   *
   * The null type flattens away, and an enum member that does not match the
   * flattened type would be rejected by providers that check enum types
   * (#3583072). Optionality is conveyed by absence from 'required'. The
   * strip is unconditional, so an author-declared null goes the same way as
   * one the normalizer added, and an enum without a null is untouched.
   */
  public function testDropsNullFromEnum(): void {
    $property = $this->getConverter()->convert('sort_order', [
      'oneOf' => [['type' => 'string'], ['type' => 'null']],
      'enum' => ['ASC', 'DESC', NULL],
    ]);
    $rendered = $property->renderPropertyArray();
    $this->assertSame('string', $rendered['type']);
    $this->assertSame(['ASC', 'DESC'], $rendered['enum']);

    $property = $this->getConverter()->convert('mode', [
      'type' => ['string', 'null'],
      'enum' => ['live', NULL],
    ]);
    $rendered = $property->renderPropertyArray();
    $this->assertSame('string', $rendered['type']);
    $this->assertSame(['live'], $rendered['enum']);

    // An author-declared null on a non-nullable type is stripped the same
    // way: the strip does not track who added the member.
    $property = $this->getConverter()->convert('state', [
      'type' => 'string',
      'enum' => ['open', NULL],
    ]);
    $this->assertSame(['open'], $property->renderPropertyArray()['enum']);

    // An enum without a null passes through untouched.
    $property = $this->getConverter()->convert('plain', [
      'type' => 'string',
      'enum' => ['a', 'b'],
    ]);
    $this->assertSame(['a', 'b'], $property->renderPropertyArray()['enum']);
  }

  /**
   * Tests nested property names are escaped like top-level names.
   *
   * The AI module rejects non-alphanumeric property names outright, so
   * colon-containing names must be escaped at every level. The value-side
   * counterpart (ToolPluginBase unescaping nested keys before execution)
   * is covered by ToolPluginBaseStructuredOutputTest.
   */
  public function testNestedPropertyNamesEscaped(): void {
    $property = $this->getConverter()->convert('payload:input', [
      'description' => 'Payload.',
      'properties' => [
        'field:tags' => [
          'type' => 'string',
          'description' => 'A colon-containing nested key.',
        ],
      ],
      'required' => ['field:tags'],
    ], TRUE);

    $rendered = $property->renderPropertyArray();
    $this->assertSame('payload__colon__input', $rendered['name']);
    $this->assertArrayHasKey('field__colon__tags', $rendered['properties']);
    $this->assertArrayNotHasKey('field:tags', $rendered['properties']);
  }

  /**
   * Tests \ArrayObject schema values are cast to arrays before conversion.
   */
  public function testConvertsArrayObjectSchemaValues(): void {
    $property = $this->getConverter()->convert('context', [
      'type' => 'object',
      'description' => 'An empty map.',
      'properties' => new \ArrayObject(),
    ]);
    $this->assertNull($property->getProperties());

    $property = $this->getConverter()->convert('outer', [
      'type' => 'object',
      'properties' => [
        'inner' => [
          'type' => 'object',
          'description' => 'A nested empty map.',
          'properties' => new \ArrayObject(),
        ],
      ],
    ]);
    $rendered = $property->renderPropertyArray();
    $this->assertArrayHasKey('inner', $rendered['properties']);
  }

  /**
   * Tests empty properties members are dropped from passthrough schemas.
   *
   * An items schema is rendered into the provider payload untouched, so an
   * empty properties member (\ArrayObject in the serializer's output) would
   * re-encode as [] once cast to a PHP array — invalid JSON Schema draft
   * 2020-12 and rejected by strict providers (e.g. Anthropic). The shape
   * mirrors a free-form multiple map input such as field_set_value's value.
   */
  public function testDropsEmptyPropertiesFromItemsSchema(): void {
    $property = $this->getConverter()->convert('value', [
      'type' => 'array',
      'description' => 'Array of Value',
      'items' => [
        'type' => 'object',
        'description' => 'The values to set.',
        'properties' => new \ArrayObject(),
      ],
    ]);

    $rendered = $property->renderPropertyArray();
    $this->assertSame('array', $rendered['type']);
    $this->assertSame('object', $rendered['items']['type']);
    $this->assertArrayNotHasKey('properties', $rendered['items']);
    $this->assertStringNotContainsString('"properties":[]', json_encode($rendered));
  }

  /**
   * Gets the schema to tools property converter.
   *
   * @return \Drupal\tool_ai_connector\SchemaToolsPropertyConverter
   *   The converter service.
   */
  protected function getConverter(): SchemaToolsPropertyConverter {
    $converter = $this->container->get('tool_ai_connector.schema_tools_property_converter');
    $this->assertInstanceOf(SchemaToolsPropertyConverter::class, $converter);
    return $converter;
  }

}
