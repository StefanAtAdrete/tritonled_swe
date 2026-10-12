<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Normalizer;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the JSON Schema examples keyword emitted for input definitions.
 *
 * Output definitions share the keyword; one test here covers them.
 *
 * Examples are annotations, not validation constraints: they are emitted
 * verbatim next to default, survive the optional-input null handling, and
 * are absent when a definition declares none. Emitted examples should also
 * validate against the schema that carries them — JSON Schema does not
 * require this, but an LLM contract with invalid examples steers callers
 * into guaranteed validation failures.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class InputExamplesEmissionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'tool_test',
  ];

  /**
   * Normalizes a definition to JSON Schema through the serializer.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The input or output definition.
   *
   * @return array
   *   The emitted JSON Schema.
   */
  protected function normalize(InputDefinitionInterface|OutputDefinitionInterface $definition): array {
    return $this->container->get('serializer')->normalize($definition, 'json_schema');
  }

  /**
   * Builds a string input definition.
   *
   * @param bool $required
   *   Whether the input is required.
   *
   * @return \Drupal\tool\TypedData\InputDefinition
   *   The definition.
   */
  protected function stringDefinition(bool $required = TRUE): InputDefinition {
    return new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Machine name'),
      description: new TranslatableMarkup('A machine name.'),
      required: $required,
    );
  }

  /**
   * Tests that declared examples are emitted and undeclared ones are not.
   */
  public function testExamplesEmittedWhenDeclared(): void {
    $definition = $this->stringDefinition();
    $definition->setExamples(['field_tags', 'field_summary']);
    $schema = $this->normalize($definition);
    $this->assertSame(['field_tags', 'field_summary'], $schema['examples']);

    // String keys do not leak into the emitted array.
    $definition = $this->stringDefinition();
    $definition->setExamples(['simple' => 'field_tags', 'nested' => 'field_body']);
    $schema = $this->normalize($definition);
    $this->assertSame(['field_tags', 'field_body'], $schema['examples']);

    $schema = $this->normalize($this->stringDefinition());
    $this->assertArrayNotHasKey('examples', $schema);
  }

  /**
   * Tests that examples survive the optional-input null handling.
   */
  public function testExamplesSurviveOptionalNullBranch(): void {
    $definition = $this->stringDefinition(required: FALSE);
    $definition->setExamples(['field_tags']);
    $schema = $this->normalize($definition);

    // The optional path rewrote the type shape but left the annotation.
    $this->assertTrue(isset($schema['oneOf']) || (isset($schema['type']) && is_array($schema['type'])));
    $this->assertSame(['field_tags'], $schema['examples']);
  }

  /**
   * Tests that emitted examples validate against their emitted schema.
   */
  public function testExamplesValidateAgainstEmittedSchema(): void {
    $definition = new InputDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Weight'),
      description: new TranslatableMarkup('A weight.'),
      required: FALSE,
      constraints: ['Range' => ['min' => 0, 'max' => 100]],
    );
    $definition->setExamples([0, 50, 100]);
    $this->assertExamplesValid($this->normalize($definition));
  }

  /**
   * Tests that output definitions emit examples the way inputs do.
   */
  public function testOutputExamplesEmitted(): void {
    $definition = new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Status'),
      description: new TranslatableMarkup('The order status.'),
      examples: ['draft', 'completed'],
    );
    $schema = $this->normalize($definition);
    $this->assertSame(['draft', 'completed'], $schema['examples']);
    $this->assertExamplesValid($schema);

    $definition->setMultiple();
    $schema = $this->normalize($definition);
    $this->assertSame(['draft', 'completed'], $schema['items']['examples']);
    $this->assertArrayNotHasKey('examples', $schema);
  }

  /**
   * Tests examples declared in the attribute through real plugin discovery.
   *
   * Examples are authored against the definition as declared; a subscriber
   * or refiner that re-types an input must update or clear them. Validating
   * the emitted schema catches the mismatch at test time instead of
   * stripping at runtime.
   */
  public function testAttributeDeclaredExamplesEmitted(): void {
    $serializer = $this->container->get('tool.definition_serializer');
    $tool = $this->container->get('plugin.manager.tool')->createInstance('example_values_tool');
    $schema = $serializer->normalizeInputSchema($tool);
    $properties = $schema['properties'] instanceof \ArrayObject
      ? $schema['properties']->getArrayCopy()
      : $schema['properties'];

    $this->assertSame(['field_tags', 'field_summary'], $properties['field_name']['examples']);
    $this->assertSame([0, 50, 100], $properties['weight']['examples']);

    // A list input carries its examples on the single-value item schema;
    // the array level stays clean.
    $this->assertSame(['alpha', 'beta'], $properties['aliases']['items']['examples']);
    $this->assertArrayNotHasKey('examples', $properties['aliases']);

    $this->assertExamplesValid($schema);
  }

  /**
   * Recursively validates every examples entry against its host schema.
   *
   * @param array $schema
   *   The JSON Schema to walk.
   */
  protected function assertExamplesValid(array $schema): void {
    if (isset($schema['examples']) && is_array($schema['examples'])) {
      // Decode through JSON so \ArrayObject members become objects and the
      // validator sees the schema as a consumer would.
      $decoded = json_decode((string) json_encode($schema));
      foreach ($schema['examples'] as $example) {
        $validator = new Validator();
        $value = json_decode((string) json_encode($example));
        $validator->validate($value, $decoded);
        $this->assertTrue($validator->isValid(), sprintf(
          'Example %s does not validate against its schema: %s',
          json_encode($example),
          json_encode($validator->getErrors()),
        ));
      }
    }
    foreach ($schema as $member) {
      if (is_array($member)) {
        $this->assertExamplesValid($member);
      }
    }
  }

}
