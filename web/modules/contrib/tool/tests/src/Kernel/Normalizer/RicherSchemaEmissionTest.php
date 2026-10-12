<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Normalizer;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\tool\TypedData\InputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Snapshot tests for the JSON Schema emitted for tool input definitions.
 *
 * Asserts the full schema produced for each constraint and type-format
 * combination on ComprehensiveTypesTool, on both scalar paths: pre-11.1
 * cores use LegacyTypedDataNormalizer (optional scalars emit
 * `type: [X, null]`), 11.1+ cores use core's schema-aware normalizers
 * (optional scalars emit `oneOf`).
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class RicherSchemaEmissionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'tool_test',
    'node',
    'field',
    'text',
  ];

  /**
   * The tool definition serializer.
   *
   * @var \Drupal\tool\Normalizer\ToolDefinitionSerializer
   */
  protected $definitionSerializer;

  /**
   * Normalized input definitions of comprehensive_types_tool, keyed by name.
   *
   * @var array
   */
  protected array $normalized;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    $this->definitionSerializer = $this->container->get('tool.definition_serializer');
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->normalized = $this->definitionSerializer->normalizeInputDefinitions($tool);
  }

  /**
   * Whether core's schema-aware normalizers (11.1+) handle scalars.
   */
  protected function isCoreSchemaPath(): bool {
    return version_compare(\Drupal::VERSION, '11.1', '>=');
  }

  /**
   * Builds the expected schema of an optional scalar for the active path.
   *
   * @param string $type
   *   The JSON Schema type of the non-null branch.
   * @param array $extra
   *   Additional schema keywords emitted alongside the type.
   *
   * @return array
   *   The expected schema.
   */
  protected function optionalScalar(string $type, array $extra = []): array {
    $schema = $this->isCoreSchemaPath()
      ? ['oneOf' => [['type' => $type], ['type' => 'null']]]
      : ['type' => [$type, 'null']];
    return $schema + $extra;
  }

  /**
   * Tests that a Regex constraint is emitted as an ECMA-262 pattern.
   */
  public function testRegexConstraintEmitsPattern(): void {
    $this->assertEquals($this->optionalScalar('string', [
      'pattern' => '^[a-z]{3}-\d{2}$',
      'title' => 'Code',
      'description' => 'Code: A pattern-constrained string input.',
    ]), $this->normalized['code']);
  }

  /**
   * Tests that decimals emit a patterned string on both paths.
   */
  public function testDecimalEmitsPatternedString(): void {
    $this->assertEquals([
      'type' => ['string', 'null'],
      'pattern' => '^-?\d+(\.\d+)?$',
      'title' => 'Price',
      'description' => 'Price: A decimal input. A decimal number formatted as a string.',
    ], $this->normalized['price']);
  }

  /**
   * Tests the datetime_iso8601 schema.
   */
  public function testDateTimeEmitsDateTimeFormat(): void {
    $this->assertEquals([
      'type' => ['string', 'null'],
      'format' => 'date-time',
      'title' => 'Published at',
      'description' => 'Published at: A datetime input.',
    ], $this->normalized['published_at']);
  }

  /**
   * Tests the timestamp schema on both paths.
   *
   * The value normalization differs per core: pre-11.1 timestamps pass
   * through as integers, while 11.1+ cores normalize them to RFC 3339
   * strings — so each path documents its own wire format.
   */
  public function testTimestampSchemaMatchesWireFormat(): void {
    if ($this->isCoreSchemaPath()) {
      $this->assertEquals([
        'type' => ['string', 'null'],
        'format' => 'date-time',
        'title' => 'Changed',
        'description' => 'Changed: A timestamp input.',
      ], $this->normalized['changed']);
    }
    else {
      $this->assertEquals([
        'type' => ['integer', 'null'],
        'title' => 'Changed',
        'description' => 'Changed: A timestamp input. A Unix timestamp in seconds.',
      ], $this->normalized['changed']);
    }
  }

  /**
   * Tests that comparison constraints map to numeric bounds.
   */
  public function testComparisonConstraintsEmitBounds(): void {
    $this->assertEquals($this->optionalScalar('integer', [
      'exclusiveMinimum' => 0,
      'maximum' => 100,
      'title' => 'Score',
      'description' => 'Score: A comparison-constrained integer input.',
    ]), $this->normalized['score']);
  }

  /**
   * Tests that a Count constraint maps to minItems/maxItems.
   */
  public function testCountConstraintEmitsItemBounds(): void {
    $this->assertEquals([
      'type' => ['array', 'null'],
      'items' => ['type' => 'string'],
      'title' => 'Labels',
      'description' => 'Labels: A count-constrained multiple string input.',
      'minItems' => 1,
      'maxItems' => 5,
    ], $this->normalized['labels']);
  }

  /**
   * Tests format-mapped constraints (Email, Uuid).
   */
  public function testFormatConstraintsEmitFormats(): void {
    $this->assertEquals($this->optionalScalar('string', [
      'format' => 'email',
      'title' => 'Contact email',
      'description' => 'Contact email: An email-constrained string input.',
    ]), $this->normalized['contact_email']);
    $this->assertEquals($this->optionalScalar('string', [
      'format' => 'uuid',
      'title' => 'External ID',
      'description' => 'External ID: A UUID-constrained string input.',
    ]), $this->normalized['external_id']);
  }

  /**
   * Tests that NotBlank emits minLength and suppresses the null branch.
   */
  public function testNotBlankEmitsMinLengthWithoutNull(): void {
    $this->assertEquals([
      'type' => 'string',
      'minLength' => 1,
      'title' => 'Nickname',
      'description' => 'Nickname: A not-blank string input.',
    ], $this->normalized['nickname']);
  }

  /**
   * Tests that NotNull suppresses the null branch on optional inputs.
   */
  public function testNotNullSuppressesNullBranch(): void {
    $this->assertEquals([
      'type' => 'integer',
      'title' => 'Threshold',
      'description' => 'Threshold: An optional but not nullable integer input.',
    ], $this->normalized['threshold']);
  }

  /**
   * Tests that PositiveOrZero maps to a zero minimum.
   */
  public function testPositiveOrZeroEmitsMinimum(): void {
    $this->assertEquals($this->optionalScalar('integer', [
      'minimum' => 0,
      'title' => 'Rating',
      'description' => 'Rating: A positive-or-zero integer input.',
    ]), $this->normalized['rating']);
  }

  /**
   * Tests an optional IdenticalTo widens its const to an enum with null.
   *
   * The input is optional, so null is allowed beside the constant and the
   * const widens to an enum of both (#3583072). A required IdenticalTo
   * keeps its plain const: null is not allowed, so nothing widens.
   */
  public function testIdenticalToWidensConstWhenOptional(): void {
    $this->assertEquals($this->optionalScalar('string', [
      'enum' => ['live', NULL],
      'title' => 'Mode',
      'description' => 'Mode: An identical-to string input.',
    ]), $this->normalized['mode']);

    $schema = $this->container->get('serializer')->normalize(
      new InputDefinition('string', 'Mode', 'The mode.', TRUE, FALSE, NULL, ['IdenticalTo' => ['value' => 'live']]),
      'json_schema',
    );
    $this->assertSame('live', $schema['const']);
    $this->assertArrayNotHasKey('enum', $schema);
  }

  /**
   * Tests that NotBlank on a list emits minItems and suppresses null.
   *
   * Required alone advertises nothing: an empty list is a value, and the
   * minimum comes only from an explicit NotBlank or Count (#3583069).
   */
  public function testNotBlankListEmitsMinItemsWithoutNull(): void {
    $this->assertEquals([
      'type' => 'array',
      'items' => ['type' => 'string'],
      'minItems' => 1,
      'description' => 'Keywords: A not-blank list of strings.',
      'title' => 'Keywords',
    ], $this->normalized['keywords']);
  }

  /**
   * Tests that EntityBundleExists enumerates the entity type's bundles.
   */
  public function testEntityBundleExistsEmitsEnum(): void {
    $this->assertEquals($this->optionalScalar('string', [
      'enum' => ['article', NULL],
      'title' => 'Bundle',
      'description' => 'Bundle: A bundle-constrained string input.',
    ]), $this->normalized['bundle']);
  }

  /**
   * Tests that CountryCode maps to a two-letter pattern, not a giant enum.
   */
  public function testCountryCodeEmitsPattern(): void {
    $this->assertEquals($this->optionalScalar('string', [
      'pattern' => '^[A-Z]{2}$',
      'title' => 'Country',
      'description' => 'Country: A country-code string input.',
    ]), $this->normalized['country']);
  }

  /**
   * Tests that the registered comparison constraint plugins validate.
   *
   * GreaterThan/LessThanOrEqual are Symfony constraints registered as
   * plugins by this module; without registration, any provided value fails
   * with an unknown-plugin violation.
   */
  public function testComparisonConstraintsValidateValues(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertCount(0, $tool->validateInputValues(['score' => 50]));
    $this->assertCount(1, $tool->validateInputValues(['score' => 0]));
    $this->assertCount(1, $tool->validateInputValues(['score' => 150]));
  }

  /**
   * Tests that a map without properties keeps an object-valued properties.
   */
  public function testEmptyMapEmitsObjectProperties(): void {
    $this->assertEquals([
      'type' => ['object', 'null'],
      'properties' => new \ArrayObject(),
      'title' => 'Attributes',
      'description' => 'Attributes: A map without declared properties.',
    ], $this->normalized['attributes']);
    $this->assertStringContainsString('"properties":{}', json_encode($this->normalized['attributes']));
  }

  /**
   * Tests that a tool without inputs encodes properties as an empty object.
   */
  public function testEmptyInputSchemaEncodesPropertiesAsObject(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('no_requirements_tool');
    $schema = $this->definitionSerializer->normalizeInputSchema($tool);
    $this->assertStringContainsString('"properties":{}', json_encode($schema));
  }

  /**
   * Tests that only the input root forbids undeclared properties.
   *
   * Maps must stay open: refiners may add properties once dependency values
   * arrive, and maps without declared properties are free-form.
   */
  public function testOnlyInputRootForbidsUndeclaredProperties(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $schema = $this->definitionSerializer->normalizeInputSchema($tool);
    $this->assertFalse($schema['additionalProperties']);
    $this->assertArrayNotHasKey('additionalProperties', $schema['properties']['metadata']);
    $this->assertArrayNotHasKey('additionalProperties', $schema['properties']['attributes']);
  }

}
