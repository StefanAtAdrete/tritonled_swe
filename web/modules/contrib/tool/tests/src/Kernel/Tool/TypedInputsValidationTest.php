<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Tests validation for typed tool inputs.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedInputsTrait
 */
#[RunTestsInSeparateProcesses]
class TypedInputsValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   */
  protected ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests validateInputs() convenience method returns violations directly.
   *
   * @covers ::validateInputs
   */
  public function testValidateInputsReturnsViolationsWithoutThrowing(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    // No input set — required 'number' input is missing.
    $violations = $tool->validateInputs();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertStringContainsString('number', (string) $violations);
  }

  /**
   * Tests validateInputs() handles an explicitly NULL required input.
   *
   * @covers ::validateInputs
   */
  public function testValidateInputsReturnsViolationForExplicitNull(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setInputValue('number', NULL);

    $violations = $tool->validateInputs();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertStringContainsString('number', (string) $violations);
  }

  /**
   * Tests validateInputs() handles an explicitly NULL configuration value.
   *
   * @covers ::validateInputs
   */
  public function testValidateInputsReturnsViolationForNullConfiguration(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setConfiguration(['number' => NULL]);

    $violations = $tool->validateInputs();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertStringContainsString('number', (string) $violations);
  }

  /**
   * Tests an empty context resolves to its definition default.
   *
   * @covers ::validateInputs
   */
  public function testValidateInputsUsesDefaultForExplicitNullContexts(): void {
    foreach (['input', 'configuration'] as $source) {
      $tool = $this->toolManager->createInstance('numeric_input_tool');
      $tool->getInputDefinition('number')->setDefaultValue(7);
      if ($source === 'input') {
        $tool->setInputValue('number', NULL);
      }
      else {
        $tool->setConfiguration(['number' => NULL]);
      }

      $this->assertCount(0, $tool->validateInputs(), $source);
      $this->assertSame(7, $tool->getExecutableValue('number'), $source);
    }
  }

  /**
   * Tests validateInputs() returns empty list for valid inputs.
   *
   * @covers ::validateInputs
   */
  public function testValidateInputsPassesWithValidInputs(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setInputValue('number', 42);

    $violations = $tool->validateInputs();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests every item in a multiple input is validated.
   *
   * @covers ::validateInputValue
   */
  public function testValidatesAllMultipleInputItems(): void {
    $violations = $this->getTool()->validateInputValue(
      $this->getMultipleStringDefinition(),
      ['valid', 'x', 'y'],
    );

    $this->assertCount(2, $violations);
    $this->assertSame(['x', 'y'], array_map(
      static fn($violation) => $violation->getInvalidValue(),
      iterator_to_array($violations),
    ));
    // Messages name the failing position so a caller correcting a long list
    // knows which element to fix (#3582982).
    $this->assertStringContainsString('(item 1)', (string) $violations->get(0)->getMessage());
    $this->assertStringContainsString('(item 2)', (string) $violations->get(1)->getMessage());
  }

  /**
   * Tests valid multiple input items produce no violations.
   *
   * @covers ::validateInputValue
   */
  public function testValidMultipleInputItemsPassValidation(): void {
    $violations = $this->getTool()->validateInputValue(
      $this->getMultipleStringDefinition(),
      ['valid', 'also valid'],
    );

    $this->assertCount(0, $violations);
  }

  /**
   * Tests an empty required multiple input is valid without a count constraint.
   *
   * Required validates that an input value is present; a minimum item count
   * must be expressed with an explicit Count constraint.
   *
   * @covers ::validateInputValue
   */
  public function testRequiredMultipleInputAllowsEmptyArray(): void {
    $violations = $this->getTool()->validateInputValue(
      $this->getMultipleStringDefinition(),
      [],
    );

    $this->assertCount(0, $violations);
  }

  /**
   * Tests every item in a ListInputDefinition is validated.
   *
   * List definitions have isMultiple() FALSE with data type 'list', so they
   * bypass the multiple-input branch; typed-data validation does not surface
   * ItemList item violations either, making the explicit per-item pass the
   * only gate (#3582982).
   *
   * @covers ::validateInputValue
   */
  public function testValidatesListDefinitionItems(): void {
    $tool = $this->toolManager->createInstance('coercion_test_tool');
    $definition = $tool->getInputDefinition('numbers');

    $violations = $tool->validateInputValue($definition, ['not an int', 5]);
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('(item 0)', (string) $violations->get(0)->getMessage());

    $this->assertCount(0, $tool->validateInputValue($definition, [1, 2, 3]));
  }

  /**
   * Provides values for a list declaring constraints on itself and its items.
   *
   * @return \Generator
   *   Cases of [list value, expected violation messages as plain text].
   */
  public static function listConstraintProvider(): \Generator {
    yield 'too few items' => [
      ['abcd'],
      ['This collection should contain 2 elements or more.'],
    ];
    yield 'item too short' => [
      ['abcd', 'x'],
      ['(item 1) This value is too short. It should have 3 characters or more.'],
    ];
    yield 'empty list' => [
      [],
      ['This collection should contain 2 elements or more.'],
    ];
  }

  /**
   * Tests the constraints declared on a ListInputDefinition are validated.
   *
   * The list's own constraints, such as Count, bind the list as a whole,
   * while the item definition's constraints bind each item. Each failure is
   * reported once: item violations must not repeat at the list level.
   *
   * @param array $value
   *   The list value.
   * @param string[] $expected
   *   The expected violation messages as plain text.
   *
   * @covers ::validateInputValue
   * @dataProvider listConstraintProvider
   */
  #[DataProvider('listConstraintProvider')]
  public function testValidatesListDefinitionConstraints(array $value, array $expected): void {
    $violations = $this->getTool()->validateInputValue(
      $this->getConstrainedListDefinition(),
      $value,
    );

    $this->assertCount(count($expected), $violations);
    $this->assertSame($expected, array_map(
      static fn($violation) => strip_tags((string) $violation->getMessage()),
      iterator_to_array($violations),
    ));
  }

  /**
   * Provides values for a map declaring constraints on itself and its members.
   *
   * @return \Generator
   *   Cases of [map value, expected violation messages as plain text].
   */
  public static function mapConstraintProvider(): \Generator {
    yield 'too few properties' => [
      ['primary' => 'abcd'],
      ['This collection should contain 2 elements or more.'],
    ];
    yield 'property too short' => [
      ['secondary' => 'x'],
      [
        '(property secondary) This value is too short. It should have 3 characters or more.',
        'This collection should contain 2 elements or more.',
      ],
    ];
    yield 'empty map' => [
      [],
      ['This collection should contain 2 elements or more.'],
    ];
  }

  /**
   * Tests the constraints declared on a MapInputDefinition are validated.
   *
   * The map's own constraints, such as Count, bind the map as a whole -
   * Count counts its properties - while each property's constraints bind
   * that property. Each failure is reported once: property violations must
   * not repeat at the map level.
   *
   * @param array $value
   *   The map value.
   * @param string[] $expected
   *   The expected violation messages as plain text.
   *
   * @covers ::validateInputValue
   * @dataProvider mapConstraintProvider
   */
  #[DataProvider('mapConstraintProvider')]
  public function testValidatesMapDefinitionConstraints(array $value, array $expected): void {
    $violations = $this->getTool()->validateInputValue(
      $this->getConstrainedMapDefinition(),
      $value,
    );

    $this->assertCount(count($expected), $violations);
    $this->assertSame($expected, array_map(
      static fn($violation) => strip_tags((string) $violation->getMessage()),
      iterator_to_array($violations),
    ));
  }

  /**
   * Tests a multiple input validates as the list it is converted to.
   *
   * The constraints of the list above, declared on a multiple input: a tool
   * definition converts it to a list with a deprecation (#3583049), after
   * which Count binds the list and Length each item, exactly as they do on
   * the explicit list.
   *
   * @param array $value
   *   The list value.
   * @param string[] $expected
   *   The expected violation messages as plain text.
   *
   * @covers ::validateInputValue
   * @dataProvider listConstraintProvider
   */
  #[DataProvider('listConstraintProvider')]
  #[IgnoreDeprecations]
  public function testValidatesConvertedMultipleConstraints(array $value, array $expected): void {
    $this->expectDeprecation("Declaring the 'codes' input with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $definition = (new ToolDefinition(['id' => 'converted_tool']))
      ->addInputDefinition('codes', new InputDefinition(
        data_type: 'string',
        label: 'Codes',
        description: 'A count-constrained multiple string input.',
        multiple: TRUE,
        constraints: ['Count' => ['min' => 2], 'Length' => ['min' => 3]],
      ))
      ->getInputDefinition('codes');
    $this->assertInstanceOf(ListInputDefinition::class, $definition);

    $violations = $this->getTool()->validateInputValue($definition, $value);
    $this->assertCount(count($expected), $violations);
    $this->assertSame($expected, array_map(
      static fn($violation) => strip_tags((string) $violation->getMessage()),
      iterator_to_array($violations),
    ));
  }

  /**
   * Tests a structure-reading constraint on a map input through the tool.
   *
   * ComplexData names a property the map declares, so validateInputs()
   * reports its Length violation once, on that property, rather than
   * throwing because the property is unknown (#3583060).
   *
   * @covers ::validateInputs
   * @covers ::validateDeclaredConstraints
   */
  public function testStructureReadingMapConstraintThroughValidateInputs(): void {
    $tool = $this->getTool();
    $tool->setInputDefinitions([
      'product' => new MapInputDefinition(
        label: 'Product',
        description: 'A product with a stock keeping unit.',
        constraints: ['ComplexData' => ['sku' => ['Length' => ['min' => 3]]]],
        property_definitions: [
          'sku' => new InputDefinition(
            data_type: 'string',
            label: 'SKU',
            description: 'The stock keeping unit.',
          ),
        ],
      ),
    ]);

    $tool->setInputValue('product', ['sku' => 'x']);
    $violations = $tool->validateInputs();
    $this->assertCount(1, $violations);
    $this->assertSame('sku', $violations->get(0)->getPropertyPath());
    $this->assertSame(
      'Input product: This value is too short. It should have 3 characters or more.',
      strip_tags((string) $violations->get(0)->getMessage()),
    );

    $tool->setInputValue('product', ['sku' => 'abcd']);
    $this->assertCount(0, $tool->validateInputs());
  }

  /**
   * Provides values for a map declaring a structure-reading constraint.
   *
   * @return \Generator
   *   Cases of [map constraints, map value, expected violations as pairs of
   *   property path and plain text message].
   */
  public static function structureReadingMapConstraintProvider(): \Generator {
    $sku_length = ['ComplexData' => ['sku' => ['Length' => ['min' => 3]]]];
    yield 'property too short' => [
      $sku_length,
      ['sku' => 'x'],
      [['sku', 'This value is too short. It should have 3 characters or more.']],
    ];
    yield 'valid property' => [$sku_length, ['sku' => 'abcd'], []];
    yield 'property with constraints of its own' => [
      ['ComplexData' => ['code' => ['Regex' => ['pattern' => '/^[a-z]+$/']]]],
      ['code' => 'X'],
      [
        ['', '(property code) This value is too short. It should have 3 characters or more.'],
        ['code', 'This value is not valid.'],
      ],
    ];
    yield 'property of the wrong type' => [
      $sku_length,
      ['sku' => ['x']],
      [['', '(property sku) This value should be of the correct primitive type.']],
    ];
    yield 'nested map that is not an array' => [
      ['ComplexData' => ['settings' => ['NotNull' => []]]],
      ['settings' => 'text'],
      [
        [
          '',
          '(property settings) The Settings input is invalid: Invalid values given. Values must be represented as an associative array.',
        ],
      ],
    ];
    yield 'count after a property of the wrong type' => [
      $sku_length + ['Count' => ['min' => 2]],
      ['sku' => ['x']],
      [
        ['', '(property sku) This value should be of the correct primitive type.'],
        ['', 'This collection should contain 2 elements or more.'],
      ],
    ];
    yield 'count after reading an absent property' => [
      ['ComplexData' => ['code' => ['NotNull' => []]], 'Count' => ['max' => 1]],
      ['sku' => 'abcd'],
      [['code', 'This value should not be null.']],
    ];
  }

  /**
   * Tests a map's structure-reading constraints run against its properties.
   *
   * ComplexData reaches the properties the map declares, and each fault is
   * reported once: the constraints a property declares for itself run in the
   * property walk only, and a property value the walk already rejected does
   * not make the map's own constraints throw (#3583060).
   *
   * @param array $constraints
   *   The constraints declared on the map.
   * @param array $value
   *   The map value.
   * @param array $expected
   *   The expected violations as pairs of property path and plain text
   *   message.
   *
   * @covers ::validateInputValue
   * @covers ::validateDeclaredConstraints
   * @dataProvider structureReadingMapConstraintProvider
   */
  #[DataProvider('structureReadingMapConstraintProvider')]
  public function testValidatesStructureReadingMapConstraints(array $constraints, array $value, array $expected): void {
    $violations = $this->getTool()->validateInputValue(
      $this->getStructuredMapDefinition($constraints),
      $value,
    );

    $this->assertSame($expected, array_map(
      static fn($violation) => [$violation->getPropertyPath(), strip_tags((string) $violation->getMessage())],
      iterator_to_array($violations),
    ));
  }

  /**
   * Tests an item violation of a converted multiple input names the input.
   *
   * The item of the list a multiple input converts to carries no label
   * (#3583049), so a message built from it names the list instead, as it
   * named the multiple input before the conversion.
   *
   * @covers ::validateInputValue
   */
  #[IgnoreDeprecations]
  public function testConvertedMultipleItemViolationNamesInput(): void {
    $this->expectDeprecation("Declaring the 'accounts' input with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $definition = (new ToolDefinition(['id' => 'converted_tool']))
      ->addInputDefinition('accounts', new EntityInputDefinition(
        data_type: 'user',
        label: 'Accounts',
        description: 'Some accounts.',
        multiple: TRUE,
      ))
      ->getInputDefinition('accounts');
    $this->assertInstanceOf(ListInputDefinition::class, $definition);
    $this->assertSame('', (string) $definition->getItemDefinition()->getLabel());

    $violations = $this->getTool()->validateInputValue($definition, ['not-an-entity']);
    $this->assertCount(1, $violations);
    $this->assertSame('(item 0) The Accounts input must be an entity.', (string) $violations->get(0)->getMessage());
  }

  /**
   * Tests a multiple map property validates as the list it is converted to.
   *
   * A map converts a property declared with multiple: TRUE to a list with a
   * deprecation (#3583049). The list branch then checks the Count the
   * property declared, which a multiple property left unchecked, and each
   * item keeps its Length.
   *
   * @covers ::validateInputValue
   */
  #[IgnoreDeprecations]
  public function testValidatesConvertedMultipleProperty(): void {
    $this->expectDeprecation("Declaring the 'codes' property with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $map_definition = new MapInputDefinition(
      label: 'Settings',
      description: 'A map holding a multiple property.',
      property_definitions: [
        'codes' => new InputDefinition(
          data_type: 'string',
          label: 'Codes',
          description: 'A count-constrained multiple string property.',
          multiple: TRUE,
          constraints: ['Count' => ['min' => 1, 'max' => 2], 'Length' => ['min' => 3]],
        ),
      ],
    );
    $this->assertInstanceOf(ListInputDefinition::class, $map_definition->getPropertyDefinition('codes'));

    $messages = static fn ($violations): array => array_map(
      static fn($violation) => strip_tags((string) $violation->getMessage()),
      iterator_to_array($violations),
    );
    $this->assertSame(
      ['(property codes) This collection should contain 2 elements or less.'],
      $messages($this->getTool()->validateInputValue($map_definition, ['codes' => ['north', 'south', 'east']])),
    );
    $this->assertSame(
      ['(property codes) This collection should contain 1 element or more.'],
      $messages($this->getTool()->validateInputValue($map_definition, ['codes' => []])),
    );
    $this->assertSame(
      ['(property codes) (item 1) This value is too short. It should have 3 characters or more.'],
      $messages($this->getTool()->validateInputValue($map_definition, ['codes' => ['north', 'x']])),
    );
    $this->assertCount(0, $this->getTool()->validateInputValue($map_definition, ['codes' => ['north', 'south']]));
  }

  /**
   * Provides map constraints declared in a way that cannot be validated.
   *
   * @return \Generator
   *   Cases of [map constraints, map value, expected exception class].
   */
  public static function invalidMapConstraintProvider(): \Generator {
    yield 'unknown constraint name' => [
      ['NoSuchConstraint' => []],
      ['sku' => 'abcd'],
      PluginNotFoundException::class,
    ];
    yield 'unknown constraint name with a rejected property' => [
      ['NoSuchConstraint' => []],
      ['sku' => ['x']],
      PluginNotFoundException::class,
    ];
    yield 'count without options' => [
      ['Count' => []],
      ['sku' => 'abcd'],
      MissingOptionsException::class,
    ];
    yield 'complex data naming an undeclared property' => [
      ['ComplexData' => ['color' => ['NotNull' => []]]],
      ['sku' => 'abcd'],
      \InvalidArgumentException::class,
    ];
  }

  /**
   * Tests an invalid map constraint declaration throws.
   *
   * The mistake is the tool author's, so it fails loudly in development
   * rather than being reported to a caller that cannot fix the definition.
   *
   * @param array $constraints
   *   The constraints declared on the map.
   * @param array $value
   *   The map value.
   * @param class-string<\Throwable> $exception
   *   The expected exception class.
   *
   * @covers ::validateDeclaredConstraints
   * @dataProvider invalidMapConstraintProvider
   */
  #[DataProvider('invalidMapConstraintProvider')]
  public function testInvalidMapConstraintThrows(array $constraints, array $value, string $exception): void {
    $this->expectException($exception);
    $this->getTool()->validateInputValue(
      $this->getStructuredMapDefinition($constraints),
      $value,
    );
  }

  /**
   * Tests a map's declared constraints do not validate an entity property.
   *
   * An entity input is checked for its type, not validated as a whole. A
   * constraint declared on the map reaches the entity property without
   * descending into its fields, so an unsaved account with no name or email
   * passes.
   *
   * @covers ::validateDeclaredConstraints
   */
  public function testDeclaredMapConstraintsSkipEntityValidation(): void {
    $definition = new MapInputDefinition(
      label: 'Assignment',
      description: 'A map holding an account.',
      constraints: ['ComplexData' => ['account' => ['NotNull' => []]]],
      property_definitions: [
        'account' => new EntityInputDefinition(
          data_type: 'entity:user',
          label: 'Account',
          description: 'A user account.',
        ),
      ],
    );

    $violations = $this->getTool()->validateInputValue($definition, ['account' => User::create()]);
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * Tests items of a list nested inside a map are validated.
   *
   * Typed-data validation covers scalar map properties, but a list-typed
   * property hits the same ItemList hole one level down.
   *
   * @covers ::validateInputValue
   */
  public function testValidatesListItemsInsideMaps(): void {
    $map_definition = new MapInputDefinition(
      label: 'Settings',
      description: 'A map holding a list.',
      required: FALSE,
      property_definitions: [
        'numbers' => new ListInputDefinition(
          label: 'Numbers',
          description: 'A list of integers.',
          required: FALSE,
          item_definition: new InputDefinition(
            data_type: 'integer',
            label: 'Number',
            description: 'An integer item.',
          ),
        ),
      ],
    );

    $violations = $this->getTool()->validateInputValue($map_definition, [
      'numbers' => ['not an int'],
    ]);
    $this->assertCount(1, $violations);
    $message = (string) $violations->get(0)->getMessage();
    $this->assertStringContainsString('(property numbers)', $message);
    $this->assertStringContainsString('(item 0)', $message);

    $this->assertCount(0, $this->getTool()->validateInputValue($map_definition, [
      'numbers' => [1, 2],
    ]));
  }

  /**
   * Gets a tool instance that exposes typed input validation.
   */
  protected function getTool(): ToolInterface {
    return $this->toolManager->createInstance('no_requirements_tool');
  }

  /**
   * Gets a list definition declaring constraints on the list and its items.
   */
  protected function getConstrainedListDefinition(): ListInputDefinition {
    return new ListInputDefinition(
      label: 'Codes',
      description: 'A count-constrained list of strings.',
      constraints: ['Count' => ['min' => 2]],
      item_definition: new InputDefinition(
        data_type: 'string',
        label: 'Code',
        description: 'A length-constrained string item.',
        constraints: ['Length' => ['min' => 3]],
      ),
    );
  }

  /**
   * Gets a map definition declaring constraints on the map and its members.
   */
  protected function getConstrainedMapDefinition(): MapInputDefinition {
    return new MapInputDefinition(
      label: 'Codes',
      description: 'A count-constrained map of strings.',
      constraints: ['Count' => ['min' => 2]],
      property_definitions: [
        'primary' => new InputDefinition(
          data_type: 'string',
          label: 'Primary',
          description: 'A length-constrained string property.',
          required: FALSE,
          constraints: ['Length' => ['min' => 3]],
        ),
        'secondary' => new InputDefinition(
          data_type: 'string',
          label: 'Secondary',
          description: 'A length-constrained string property.',
          required: FALSE,
          constraints: ['Length' => ['min' => 3]],
        ),
      ],
    );
  }

  /**
   * Gets a map definition with plain, constrained and nested properties.
   *
   * @param array $constraints
   *   The constraints declared on the map itself.
   */
  protected function getStructuredMapDefinition(array $constraints): MapInputDefinition {
    return new MapInputDefinition(
      label: 'Product',
      description: 'A product with typed properties.',
      constraints: $constraints,
      property_definitions: [
        'sku' => new InputDefinition(
          data_type: 'string',
          label: 'SKU',
          description: 'The stock keeping unit.',
          required: FALSE,
        ),
        'code' => new InputDefinition(
          data_type: 'string',
          label: 'Code',
          description: 'A length-constrained code.',
          required: FALSE,
          constraints: ['Length' => ['min' => 3]],
        ),
        'settings' => new MapInputDefinition(
          label: 'Settings',
          description: 'A nested map.',
          required: FALSE,
          property_definitions: [
            'mode' => new InputDefinition(
              data_type: 'string',
              label: 'Mode',
              description: 'A mode name.',
              required: FALSE,
            ),
          ],
        ),
      ],
    );
  }

  /**
   * Gets a required multiple string definition with a per-item constraint.
   */
  protected function getMultipleStringDefinition(): InputDefinition {
    return new InputDefinition(
      data_type: 'string',
      label: 'Tags',
      description: 'Multiple string values.',
      multiple: TRUE,
      constraints: ['Length' => ['min' => 2]],
    );
  }

}
