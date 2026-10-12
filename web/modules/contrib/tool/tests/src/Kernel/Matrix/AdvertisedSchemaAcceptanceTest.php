<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Matrix;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\tool\Traits\SchemaSampleGeneratorTrait;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Round-trip property: schema-valid values must be accepted by every tool.
 *
 * For each discovered tool, a value conforming to the advertised input
 * schema is generated and set; the tool must report zero violations.
 * Invokers such as the MCP bridge validate arguments strictly against the
 * advertised schema before the tool sees them, so an input whose schema
 * describes something the tool then rejects is unusable over that wire —
 * every schema/acceptance bug ends up in this test's net regardless of
 * which data type or subscriber caused it.
 *
 * A second pass re-shapes the same values the way an LLM plausibly would
 * (numbers and booleans as strings, structures as JSON strings) and asserts
 * the coercion pipeline still lands them: lenient invokers (AI function
 * calling, drush) rely on that.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class AdvertisedSchemaAcceptanceTest extends KernelTestBase {

  use SchemaSampleGeneratorTrait;

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
   * A node for inputs whose values cannot be derived from the schema.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    // Deliberately unsaved: entity validation on a saved node walks core's
    // untranslatable-fields constraint, which expects an original revision.
    $this->node = Node::create([
      'type' => 'article',
      'title' => 'Sample node',
    ]);
  }

  /**
   * Tools excluded from the property, with the reason.
   *
   * @return array
   *   Reasons keyed by tool ID.
   */
  protected function skippedTools(): array {
    return [
      // Requires a nested entity graph inside required map properties;
      // its pipeline is covered by ToolComplexEventTransformTest.
      'complex_entity_event_tool' => 'nested required entities',
      // A fixture that deliberately violates the refiner contract: setting
      // its dependency input throws by design, which RefinerContractTest
      // asserts. A tool that rejects every value cannot satisfy an
      // acceptance property.
      'refiner_contract_tool' => 'throws on set by design',
      // A fixture whose create() throws, so the Drush listing commands can
      // prove one broken plugin does not hide the rest. It has no schema
      // to accept values against.
      'broken_constructor_tool' => 'throws on create by design',
    ];
  }

  /**
   * Inputs excluded from the property, with the reason.
   *
   * @return array
   *   Reasons keyed by tool ID, then input name.
   */
  protected function skippedInputs(): array {
    return [];
  }

  /**
   * Values for inputs whose schema cannot produce them generically.
   *
   * Entity inputs advertise invoker-specific representations (handles) that
   * plain invocation cannot satisfy from JSON; real entities stand in so
   * the rest of each tool still participates in the property.
   *
   * @return array
   *   Override values keyed by tool ID, then input name.
   */
  protected function sampleOverrides(): array {
    return [
      'comprehensive_types_tool' => [
        'entity' => $this->node,
        'multiple_entities' => [$this->node],
        'entity_list' => [$this->node],
      ],
      'entity_list_input_output_tool' => [
        'entities' => [$this->node],
      ],
      'entity_output_tool' => [
        'entity' => $this->node,
      ],
      // The bundle input refines to the entity type's bundles once
      // entity_type arrives; the pre-refinement schema cannot know them.
      'refining_tool' => [
        'bundle' => 'article',
      ],
      // The field input refines to the fields of the entity type and bundle
      // pair once both dependencies arrive; the pre-refinement schema knows
      // neither the bundles nor the fields. The entity_type sample comes
      // from its advertised Choice constraint and lands on 'node'.
      'multi_dependency_refining_tool' => [
        'bundle' => 'article',
        'field' => 'title',
      ],
    ];
  }

  /**
   * Tests that schema-conforming values are accepted by every tool.
   */
  public function testSchemaValidValuesAreAccepted(): void {
    $this->assertToolsAcceptGeneratedValues(FALSE);
  }

  /**
   * Tests that LLM-shaped spellings of conforming values are accepted.
   */
  public function testLlmShapedValuesAreAccepted(): void {
    $this->assertToolsAcceptGeneratedValues(TRUE);
  }

  /**
   * Tests that null is accepted wherever an input's schema offers it.
   *
   * Models routinely send null for an optional input they do not want to
   * set, and a schema that lists null in its type must then accept it as a
   * whole: an enum or const beside the type binds the value too (#3583072).
   * The sampled round trip above never picks the null branch, so this
   * checks it for every optional input whose schema allows the null type,
   * both against the schema itself and against Tool API's validation.
   */
  public function testNullIsAcceptedWhereTheSchemaOffersIt(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $serializer = $this->container->get('tool.definition_serializer');
    $skipped = $this->skippedTools();
    $failures = [];
    $covered = 0;

    foreach (array_keys($manager->getDefinitions()) as $tool_id) {
      if (isset($skipped[$tool_id])) {
        continue;
      }
      $tool = $manager->createInstance($tool_id);
      $schema = $serializer->normalizeInputSchema($tool);
      $properties = $schema['properties'];
      if ($properties instanceof \ArrayObject) {
        $properties = $properties->getArrayCopy();
      }
      foreach ($properties as $name => $property_schema) {
        $property_schema = (array) $property_schema;
        if (in_array($name, $schema['required'] ?? [], TRUE) || !$this->typeAllowsNull($property_schema)) {
          continue;
        }
        $covered++;
        // The validator takes the value by reference.
        $value = NULL;
        $validator = new Validator();
        $validator->validate($value, json_decode(json_encode($property_schema, JSON_THROW_ON_ERROR), FALSE, 512, JSON_THROW_ON_ERROR));
        foreach ($validator->getErrors() as $error) {
          $failures[] = sprintf('%s.%s: its schema rejects null: %s', $tool_id, $name, $error['message']);
        }
        foreach ($tool->validateInputValue($tool->getInputDefinition($name), NULL) as $violation) {
          $failures[] = sprintf('%s.%s: Tool API rejects null: %s', $tool_id, $name, $violation->getMessage());
        }
      }
    }

    $this->assertSame([], $failures, implode("\n", $failures));
    // Guard against silently covering nothing if discovery changes.
    $this->assertGreaterThan(10, $covered);
  }

  /**
   * Checks whether a schema's type allows null, on either scalar path.
   *
   * @param array $schema
   *   The JSON Schema of one input.
   *
   * @return bool
   *   TRUE when the type list or a oneOf branch is null.
   */
  protected function typeAllowsNull(array $schema): bool {
    if (in_array('null', (array) ($schema['type'] ?? []), TRUE)) {
      return TRUE;
    }
    foreach ($schema['oneOf'] ?? [] as $branch) {
      if ((((array) $branch)['type'] ?? NULL) === 'null') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Runs the acceptance property over every discovered tool.
   *
   * @param bool $lax
   *   TRUE to re-shape generated values the way an LLM plausibly would.
   */
  protected function assertToolsAcceptGeneratedValues(bool $lax): void {
    $manager = $this->container->get('plugin.manager.tool');
    $serializer = $this->container->get('tool.definition_serializer');
    $skipped = $this->skippedTools();
    $failures = [];
    $covered = 0;

    foreach (array_keys($manager->getDefinitions()) as $tool_id) {
      if (isset($skipped[$tool_id])) {
        continue;
      }
      $covered++;
      $tool = $manager->createInstance($tool_id);
      $schema = $serializer->normalizeInputSchema($tool);
      $overrides = $this->sampleOverrides()[$tool_id] ?? [];

      $properties = $schema['properties'];
      if ($properties instanceof \ArrayObject) {
        $properties = $properties->getArrayCopy();
      }

      $values = [];
      foreach ($properties as $name => $property_schema) {
        if (isset($this->skippedInputs()[$tool_id][$name])) {
          continue;
        }
        try {
          if (array_key_exists($name, $overrides)) {
            $values[$name] = $overrides[$name];
          }
          else {
            $value = $this->sampleFromSchema((array) $property_schema, "$tool_id.$name");
            $values[$name] = $lax ? $this->llmVariant($value) : $value;
          }
        }
        catch (\LogicException $e) {
          $failures[] = $e->getMessage();
          continue 2;
        }
      }

      foreach ($values as $name => $value) {
        try {
          $tool->setInputValue($name, $value);
        }
        catch (\Exception $e) {
          $failures[] = sprintf(
            '%s.%s rejected schema-conforming value %s at set time: %s',
            $tool_id,
            $name,
            var_export($value, TRUE),
            $e->getMessage(),
          );
          continue 2;
        }
      }

      try {
        $violations = $tool->validateInputs();
      }
      catch (\Throwable $e) {
        $failures[] = sprintf('%s: validateInputs() threw %s: %s', $tool_id, get_class($e), $e->getMessage());
        continue;
      }
      foreach ($violations as $violation) {
        $failures[] = sprintf(
          '%s: %s (values: %s)',
          $tool_id,
          $violation->getMessage(),
          json_encode(array_map(
            static fn ($value) => is_object($value) ? get_class($value) : $value,
            $values,
          )),
        );
      }
    }

    $this->assertSame([], $failures, implode("\n", $failures));
    // Guard against silently covering nothing if discovery changes.
    $this->assertGreaterThan(10, $covered);
  }

}
