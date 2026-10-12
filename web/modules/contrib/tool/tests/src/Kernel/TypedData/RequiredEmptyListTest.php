<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\TypedData;

use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\FailureCategory;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\EffectiveDataDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Drupal\tool\TypedInputsInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Tests that "required" means present, not non-empty, for lists.
 *
 * The published JSON Schema lists a required list as a plain array and a
 * required map as a plain object, which allow [] and {}, so validation must
 * allow them too, in both directions (#3583040, #3583069). Required still
 * rejects NULL. "Must not be empty" is an explicit NotBlank or Count the
 * author declares, published as minItems or minProperties.
 *
 * Definitions are added to the plugin definition or the instance inside
 * each test rather than declared on a fixture, so the discovery-wide schema
 * acceptance matrix never feeds them samples.
 *
 * @group tool
 */
#[Group('tool')]
#[RunTestsInSeparateProcesses]
final class RequiredEmptyListTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'tool_test',
    // The NotBlank case on a list of entities declares entity:node items.
    'node',
    'field',
    'text',
  ];

  /**
   * An empty required list output is valid.
   *
   * Checked on the tool, where ToolBase::execute() validates outputs, and on
   * the effective definition alone, which is what typed data built from it
   * elsewhere validates against.
   */
  #[DataProvider('emptyOutputs')]
  public function testEmptyRequiredOutputIsValid(OutputDefinitionInterface $definition, array $value): void {
    $tool = $this->createPlainStructureTool('subject', $definition);
    $tool->setOutputValue('subject', $value);
    $violations = $tool->validateOutputs();
    $this->assertCount(0, $violations, (string) $violations);

    $violations = $this->typedDataManager()->create(EffectiveDataDefinition::createFrom($definition), $value)->validate();
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * Required list and map outputs holding an empty value.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\OutputDefinitionInterface, array<mixed>}>
   *   The definition and its empty value.
   */
  public static function emptyOutputs(): iterable {
    yield 'multiple output, converted' => [
      ListOutputDefinition::fromMultiple(new OutputDefinition('string', 'Tags', 'The tags.', TRUE, TRUE)),
      [],
    ];
    yield 'list output' => [
      new ListOutputDefinition(label: 'Tags', description: 'The tags.', required: TRUE, item_definition: new OutputDefinition('string', 'Tag', 'A tag.')),
      [],
    ];
    yield 'list declared on the plain output class' => [new OutputDefinition('list', 'Rows', 'The rows.'), []];
    yield 'required list property of a map' => [
      new MapOutputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'problems' => new OutputDefinition('string', 'Problems', 'What the check found.', TRUE, TRUE),
      ]),
      ['problems' => []],
    ];
    yield 'map output without properties' => [new MapOutputDefinition('Settings', 'The settings.', TRUE), []];
    yield 'map declared on the plain output class' => [new OutputDefinition('map', 'Settings', 'The settings.'), []];
    yield 'map output whose values are all NULL' => [
      new MapOutputDefinition('Settings', 'The settings.', TRUE),
      ['theme' => NULL],
    ];
    yield 'map output with optional properties' => [
      new MapOutputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, [], [
        'name' => new OutputDefinition('string', 'Name', 'The name.', FALSE),
      ]),
      [],
    ];
    yield 'required map property of a map' => [
      new MapOutputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'settings' => new OutputDefinition('map', 'Settings', 'The settings.'),
      ]),
      ['settings' => []],
    ];
    yield 'multiple map output, converted' => [
      ListOutputDefinition::fromMultiple(new OutputDefinition('map', 'Rows', 'The rows.', TRUE, TRUE)),
      [[]],
    ];
    yield 'list of maps' => [
      new ListOutputDefinition(label: 'Rows', description: 'The rows.', required: TRUE, item_definition: new MapOutputDefinition('Row', 'A row.', TRUE)),
      [[]],
    ];
  }

  /**
   * A required scalar output still rejects NULL.
   */
  public function testNullRequiredScalarOutputIsInvalid(): void {
    $definition = EffectiveDataDefinition::createFrom(new OutputDefinition('string', 'Title', 'The title.'));
    $violations = $this->typedDataManager()->create($definition, NULL)->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('This value should not be null.', (string) $violations[0]->getMessage());
  }

  /**
   * A required list or map output set to NULL is not provided.
   */
  #[DataProvider('requiredCollectionOutputs')]
  public function testNullRequiredOutputIsNotProvided(OutputDefinitionInterface $definition): void {
    $tool = $this->createPlainStructureTool('subject', $definition);
    $tool->setOutputValue('subject', NULL);
    $this->assertMessages(['The subject output is required but was not provided.'], $tool->validateOutputs());
  }

  /**
   * Required list and map outputs.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\OutputDefinitionInterface}>
   *   The definition.
   */
  public static function requiredCollectionOutputs(): iterable {
    yield 'multiple output, converted' => [
      ListOutputDefinition::fromMultiple(new OutputDefinition('string', 'Tags', 'The tags.', TRUE, TRUE)),
    ];
    yield 'list output' => [new ListOutputDefinition(label: 'Tags', description: 'The tags.', required: TRUE, item_definition: new OutputDefinition('string', 'Tag', 'A tag.'))];
    yield 'map output without properties' => [new MapOutputDefinition('Settings', 'The settings.', TRUE)];
    yield 'map declared on the plain output class' => [new OutputDefinition('map', 'Settings', 'The settings.')];
  }

  /**
   * A NULL nested in a required list or map output is rejected.
   *
   * The message is the one NotNull reported before, with the position named.
   *
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition.
   * @param array<mixed> $value
   *   The value holding a NULL where a list or map is required.
   * @param string $message
   *   The expected violation message.
   */
  #[DataProvider('nestedNullOutputs')]
  public function testNestedNullRequiredOutputIsInvalid(OutputDefinitionInterface $definition, array $value, string $message): void {
    $tool = $this->createPlainStructureTool('subject', $definition);
    $tool->setOutputValue('subject', $value);
    $this->assertMessages([$message], $tool->validateOutputs());
  }

  /**
   * Outputs holding a NULL where a required list or map belongs.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\OutputDefinitionInterface, array<mixed>, string}>
   *   The definition, the value and the expected message.
   */
  public static function nestedNullOutputs(): iterable {
    yield 'map property' => [
      new MapOutputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'settings' => new OutputDefinition('map', 'Settings', 'The settings.'),
      ]),
      ['settings' => NULL],
      'Output subject: (property settings) This value should not be null.',
    ];
    yield 'multiple property' => [
      new MapOutputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'problems' => new OutputDefinition('string', 'Problems', 'What the check found.', TRUE, TRUE),
      ]),
      ['problems' => NULL],
      'Output subject: (property problems) This value should not be null.',
    ];
    yield 'list property' => [
      new MapOutputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'rows' => new ListOutputDefinition(label: 'Rows', description: 'The rows.', required: TRUE, item_definition: new OutputDefinition('string', 'Row', 'A row.')),
      ]),
      ['rows' => NULL],
      'Output subject: (property rows) This value should not be null.',
    ];
    yield 'multiple map item' => [
      new OutputDefinition('map', 'Rows', 'The rows.', TRUE, TRUE),
      [NULL],
      'Output subject: (item 0) This value should not be null.',
    ];
    yield 'list item' => [
      new ListOutputDefinition(label: 'Rows', description: 'The rows.', required: TRUE, item_definition: new MapOutputDefinition('Row', 'A row.', TRUE)),
      [NULL],
      'Output subject: (item 0) This value should not be null.',
    ];
  }

  /**
   * An empty required list input is valid; NULL is not.
   */
  #[DataProvider('requiredListInputs')]
  public function testRequiredListInput(InputDefinitionInterface $definition): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);

    $violations = $tool->validateInputValue($definition, []);
    $this->assertCount(0, $violations, 'An empty value is present. ' . $violations);

    $this->assertMessages(['This value should not be null.'], $tool->validateInputValue($definition, NULL));

    $violations = $this->typedDataManager()->create(EffectiveDataDefinition::createFrom($definition), [])->validate();
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * Required list inputs.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\InputDefinitionInterface}>
   *   The definition.
   */
  public static function requiredListInputs(): iterable {
    yield 'multiple input' => [new InputDefinition('string', 'Tags', 'The tags.', TRUE, TRUE)];
    yield 'list input' => [new ListInputDefinition(label: 'Tags', description: 'The tags.', required: TRUE, item_definition: new InputDefinition('string', 'Tag', 'A tag.'))];
    yield 'list declared on the plain input class' => [new InputDefinition('list', 'Rows', 'The rows.')];
    // A multiple's NotNull binds its items; the converted list accepts []
    // as every list does (#3583069).
    yield 'converted multiple declaring NotNull' => [
      ListInputDefinition::fromMultiple(new InputDefinition('string', 'Tags', 'The tags.', TRUE, TRUE, NULL, ['NotNull' => []])),
    ];
  }

  /**
   * A NULL nested in a required list or map input is rejected.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition.
   * @param array<mixed> $value
   *   The value holding a NULL where a list or map is required.
   * @param string $message
   *   The expected violation message.
   */
  #[DataProvider('nestedNullInputs')]
  public function testNestedNullRequiredInputIsInvalid(InputDefinitionInterface $definition, array $value, string $message): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);
    $this->assertMessages([$message], $tool->validateInputValue($definition, $value));
  }

  /**
   * Inputs holding a NULL where a required list or map belongs.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\InputDefinitionInterface, array<mixed>, string}>
   *   The definition, the value and the expected message.
   */
  public static function nestedNullInputs(): iterable {
    yield 'map property' => [
      new MapInputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'settings' => new InputDefinition('map', 'Settings', 'The settings.'),
      ]),
      ['settings' => NULL],
      '(property settings) This value should not be null.',
    ];
    yield 'list property' => [
      new MapInputDefinition('Document', 'A document.', TRUE, FALSE, NULL, [], [
        'rows' => new ListInputDefinition(label: 'Rows', description: 'The rows.', required: TRUE, item_definition: new InputDefinition('string', 'Row', 'A row.')),
      ]),
      ['rows' => NULL],
      '(property rows) This value should not be null.',
    ];
    yield 'list item' => [
      new ListInputDefinition(label: 'Rows', description: 'The rows.', required: TRUE, item_definition: new MapInputDefinition('Row', 'A row.', TRUE)),
      [NULL],
      '(item 0) This value should not be null.',
    ];
    yield 'multiple item' => [
      new InputDefinition('map', 'Rows', 'The rows.', TRUE, TRUE),
      [NULL],
      '(item 0) This value should not be null.',
    ];
  }

  /**
   * A required list input set to NULL fails validateInputs().
   *
   * Inputs have no value-less marker check of their own: the NULL reaches
   * the value walker, which rejects it.
   */
  public function testNullRequiredInputFailsValidateInputs(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('plain_structure_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);
    $tool->setInputDefinitions($tool->getInputDefinitions(TRUE) + [
      'tags' => new InputDefinition('list', 'Tags', 'The tags.'),
    ]);

    $this->assertMessages(['Input tags: This value should not be null.'], $tool->validateInputs(), 'A required input never set.');

    $tool->setInputValue('tags', NULL);
    $this->assertMessages(['Input tags: This value should not be null.'], $tool->validateInputs(), 'A required input set to NULL.');

    $tool->setInputValue('tags', []);
    $violations = $tool->validateInputs();
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * A declared Count constraint still sets a minimum size.
   */
  public function testCountSetsTheMinimum(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);
    $definition = new ListInputDefinition(label: 'Tags', description: 'The tags.', required: TRUE, constraints: ['Count' => ['min' => 1]], item_definition: new InputDefinition('string', 'Tag', 'A tag.'));

    $this->assertCount(1, $tool->validateInputValue($definition, []));
    $this->assertCount(0, $tool->validateInputValue($definition, ['a']));
  }

  /**
   * An empty required list or map output lets execute() succeed.
   *
   * The plain structure tool returns its options input as the summary output
   * and its rows input as the items output.
   */
  #[DataProvider('executedOutputs')]
  public function testExecuteWithEmptyRequiredOutput(string $output, string $input, OutputDefinitionInterface $definition): void {
    $tool = $this->createPlainStructureTool($output, $definition);
    $tool->setInputValue($input, []);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $result->getMessage());
    $this->assertSame([], $tool->getOutputValue($output));
  }

  /**
   * A NULL required list or map output fails execute().
   */
  #[DataProvider('executedOutputs')]
  public function testExecuteWithNullRequiredOutput(string $output, string $input, OutputDefinitionInterface $definition): void {
    $tool = $this->createPlainStructureTool($output, $definition);
    $tool->setInputValue($input, NULL);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString("The $output output is required but was not provided.", (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * Required list and map outputs of the plain structure tool.
   *
   * @return iterable<string, array{string, string, \Drupal\tool\TypedData\OutputDefinitionInterface}>
   *   The output name, the input it echoes and the output definition.
   */
  public static function executedOutputs(): iterable {
    yield 'list output' => [
      'items',
      'rows',
      new ListOutputDefinition(label: 'Items', description: 'The items.', required: TRUE, item_definition: new OutputDefinition('any', 'Item', 'An item.')),
    ];
    yield 'list declared on the plain output class' => [
      'items',
      'rows',
      new OutputDefinition('list', 'Items', 'The items.'),
    ];
    yield 'map output' => [
      'summary',
      'options',
      new MapOutputDefinition('Summary', 'The summary.', TRUE),
    ];
    yield 'map declared on the plain output class' => [
      'summary',
      'options',
      new OutputDefinition('map', 'Summary', 'The summary.'),
    ];
  }

  /**
   * An empty map output missing a required property fails execute().
   *
   * Core skips the properties of an empty map
   * (https://www.drupal.org/project/drupal/issues/3256536); the output walker
   * checks them before typed data sees the value.
   */
  public function testExecuteWithEmptyMapMissingRequiredProperty(): void {
    $tool = $this->createPlainStructureTool('summary', new MapOutputDefinition('Summary', 'The summary.', TRUE, FALSE, NULL, [], [
      'name' => new OutputDefinition('string', 'Name', 'The name.'),
    ]));
    $tool->setInputValue('options', []);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Output summary: The Name property is required.', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * An empty required map input is valid; NULL is not.
   *
   * The same rule as lists and as map outputs (#3583069): required means
   * present and not NULL, and {} is a value.
   */
  #[DataProvider('requiredMapInputs')]
  public function testRequiredMapInput(InputDefinitionInterface $definition): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);

    $violations = $tool->validateInputValue($definition, []);
    $this->assertCount(0, $violations, 'An empty value is present. ' . $violations);

    $this->assertMessages(['This value should not be null.'], $tool->validateInputValue($definition, NULL));

    $violations = $this->typedDataManager()->create(EffectiveDataDefinition::createFrom($definition), [])->validate();
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * Required map inputs whose empty value is valid.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\InputDefinitionInterface}>
   *   The definition.
   */
  public static function requiredMapInputs(): iterable {
    yield 'map input without properties' => [new MapInputDefinition('Settings', 'The settings.', TRUE)];
    yield 'map declared on the plain input class' => [new InputDefinition('map', 'Settings', 'The settings.')];
    yield 'map input with optional properties' => [
      new MapInputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, [], [
        'name' => new InputDefinition('string', 'Name', 'The name.', FALSE),
      ]),
    ];
  }

  /**
   * An empty map input missing a required property still fails.
   *
   * Core skips the properties of an empty map
   * (https://www.drupal.org/project/drupal/issues/3256536); the input walker
   * checks them before typed data sees the value.
   */
  public function testEmptyMapInputMissingRequiredPropertyIsInvalid(): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);
    $definition = new MapInputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, [], [
      'name' => new InputDefinition('string', 'Name', 'The name.'),
    ]);
    $this->assertMessages(['The Name property is required.'], $tool->validateInputValue($definition, []));
  }

  /**
   * A declared NotBlank still rejects an empty value, and is published.
   *
   * "Must not be empty" is the author's explicit constraint under the
   * required-means-present rule. On a list it validates against the
   * canonical representation, so a list of entities unwraps to [] and is
   * rejected; the schema says minItems or minProperties (#3583069).
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition.
   * @param string $keyword
   *   The schema keyword the constraint publishes.
   */
  #[DataProvider('notBlankInputs')]
  public function testNotBlankRejectsEmpty(InputDefinitionInterface $definition, string $keyword): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('comprehensive_types_tool');
    $this->assertInstanceOf(TypedInputsInterface::class, $tool);
    $this->assertMessages(['This value should not be blank.'], $tool->validateInputValue($definition, []));

    $schema = $this->container->get('serializer')->normalize($definition, 'json_schema');
    $this->assertSame(1, $schema[$keyword] ?? NULL, json_encode($schema));
  }

  /**
   * Inputs declaring NotBlank.
   *
   * @return iterable<string, array{\Drupal\tool\TypedData\InputDefinitionInterface, string}>
   *   The definition and the published keyword.
   */
  public static function notBlankInputs(): iterable {
    yield 'list of entities' => [
      new ListInputDefinition(label: 'Nodes', description: 'The nodes to export.', required: TRUE, constraints: ['NotBlank' => []], item_definition: new InputDefinition('entity:node', 'Node', 'A node.')),
      'minItems',
    ];
    yield 'list of strings' => [
      new ListInputDefinition(label: 'Tags', description: 'The tags.', required: TRUE, constraints: ['NotBlank' => []], item_definition: new InputDefinition('string', 'Tag', 'A tag.')),
      'minItems',
    ];
    yield 'free-form map' => [
      new MapInputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, ['NotBlank' => []]),
      'minProperties',
    ];
    yield 'map with declared properties' => [
      new MapInputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, ['NotBlank' => []], [
        'name' => new InputDefinition('string', 'Name', 'The name.', FALSE),
      ]),
      'minProperties',
    ];
  }

  /**
   * A Count minimum wins over the NotBlank floor; bounds publish together.
   *
   * The appliers set the NotBlank floor with `??=`, so an explicit Count
   * minimum must not be overwritten, and a Count without a minimum still
   * gets the floor (#3583069).
   */
  public function testNotBlankAndCountPublishTheirBounds(): void {
    $serializer = $this->container->get('serializer');
    $schema = $serializer->normalize(new ListInputDefinition(
      label: 'Tags',
      description: 'The tags.',
      required: TRUE,
      constraints: ['NotBlank' => [], 'Count' => ['min' => 2, 'max' => 5]],
      item_definition: new InputDefinition('string', 'Tag', 'A tag.'),
    ), 'json_schema');
    $this->assertSame(2, $schema['minItems']);
    $this->assertSame(5, $schema['maxItems']);

    $schema = $serializer->normalize(new ListInputDefinition(
      label: 'Tags',
      description: 'The tags.',
      required: TRUE,
      constraints: ['NotBlank' => [], 'Count' => ['max' => 3]],
      item_definition: new InputDefinition('string', 'Tag', 'A tag.'),
    ), 'json_schema');
    $this->assertSame(1, $schema['minItems']);
    $this->assertSame(3, $schema['maxItems']);

    $schema = $serializer->normalize(
      new MapInputDefinition('Settings', 'The settings.', TRUE, FALSE, NULL, ['NotBlank' => [], 'Count' => ['min' => 2]]),
      'json_schema',
    );
    $this->assertSame(2, $schema['minProperties']);
  }

  /**
   * The published schema does not change: no minItems appears.
   */
  public function testSchemaIsUnchanged(): void {
    $serializer = $this->container->get('serializer');
    $schema = $serializer->normalize(new OutputDefinition('string', 'Tags', 'The tags.', TRUE, TRUE), 'json_schema');
    $this->assertSame('array', $schema['type']);
    $this->assertArrayNotHasKey('minItems', $schema);

    $schema = $serializer->normalize(new MapOutputDefinition('Settings', 'The settings.', TRUE), 'json_schema');
    $this->assertSame('object', $schema['type']);
    $this->assertArrayNotHasKey('minProperties', $schema);
  }

  /**
   * Creates the plain structure tool with an output added or replaced.
   *
   * The tool's own outputs are all optional, so every violation reported
   * belongs to the given output.
   *
   * @param string $output
   *   The output name.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition.
   */
  private function createPlainStructureTool(string $output, OutputDefinitionInterface $definition): ToolInterface {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('plain_structure_tool');
    $this->assertInstanceOf(ToolInterface::class, $tool);
    $plugin_definition = $tool->getPluginDefinition();
    $this->assertInstanceOf(ToolDefinition::class, $plugin_definition);
    $plugin_definition->addOutputDefinition($output, $definition);
    return $tool;
  }

  /**
   * Asserts a violation list holds exactly the given messages.
   *
   * @param string[] $expected
   *   The expected messages, in order.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The violations.
   * @param string $message
   *   The assertion message.
   */
  private function assertMessages(array $expected, ConstraintViolationListInterface $violations, string $message = ''): void {
    $actual = [];
    foreach ($violations as $violation) {
      $actual[] = (string) $violation->getMessage();
    }
    $this->assertSame($expected, $actual, $message);
  }

  /**
   * Gets the typed data manager.
   */
  private function typedDataManager(): TypedDataManagerInterface {
    return $this->container->get('typed_data_manager');
  }

}
