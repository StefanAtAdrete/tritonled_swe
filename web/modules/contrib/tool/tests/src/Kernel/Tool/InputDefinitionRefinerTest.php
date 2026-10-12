<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the input-definition refiner flow in TypedInputsTrait.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedInputsTrait
 */
#[RunTestsInSeparateProcesses]
class InputDefinitionRefinerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
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
   * Tests that setting a dependency refines the target definition.
   *
   * @covers ::setInputValue
   */
  public function testDependencyValueTriggersRefinement(): void {
    $tool = $this->toolManager->createInstance('refining_tool');

    // Before refinement the bundle input carries no Choice constraint.
    $this->assertArrayNotHasKey('Choice', $tool->getInputDefinition('bundle')->getConstraints());

    $tool->setInputValue('entity_type', 'node');

    $constraints = $tool->getInputDefinition('bundle')->getConstraints();
    $this->assertSame(['article', 'page'], $constraints['Choice']['choices']);

    // Refinement follows the dependency when it changes again.
    $tool->setInputValue('entity_type', 'taxonomy_term');
    $constraints = $tool->getInputDefinition('bundle')->getConstraints();
    $this->assertSame(['tags'], $constraints['Choice']['choices']);
  }

  /**
   * Tests each in-place refinement starts from the original definition.
   *
   * @covers ::setInputValue
   */
  public function testInPlaceRefinementStartsFromOriginalDefinition(): void {
    $tool = $this->toolManager->createInstance('refining_tool');

    $tool->setInputValue('entity_type', 'node');
    $this->assertArrayHasKey('NotBlank', $tool->getInputDefinition('bundle')->getConstraints());

    $tool->setInputValue('entity_type', 'user');
    $constraints = $tool->getInputDefinition('bundle')->getConstraints();
    $this->assertSame(['user'], $constraints['Choice']['choices']);
    $this->assertArrayNotHasKey('NotBlank', $constraints);
  }

  /**
   * Tests the refined constraint is enforced for the target input.
   *
   * @covers ::setInputValue
   * @covers ::validateInputValues
   */
  public function testRefinedConstraintValidates(): void {
    $tool = $this->toolManager->createInstance('refining_tool');
    $tool->setInputValue('entity_type', 'node');

    $this->assertCount(0, $tool->validateInputValues(['bundle' => 'article']));
    $this->assertCount(1, $tool->validateInputValues(['bundle' => 'tags']));
  }

  /**
   * Tests that an invalid dependency reverts the refined definition.
   *
   * When the dependency value fails validation, the target reverts to its
   * original plugin-definition state rather than keeping a refinement based
   * on stale data.
   *
   * @covers ::setInputValue
   */
  public function testInvalidDependencyRevertsRefinement(): void {
    $tool = $this->toolManager->createInstance('refining_tool');

    $tool->setInputValue('entity_type', 'node');
    $this->assertArrayHasKey('Choice', $tool->getInputDefinition('bundle')->getConstraints());

    // 'bogus' violates the entity_type Choice constraint, so the bundle
    // definition reverts to its original, unconstrained form.
    $tool->setInputValue('entity_type', 'bogus');
    $this->assertArrayNotHasKey('Choice', $tool->getInputDefinition('bundle')->getConstraints());
  }

  /**
   * Tests that a value set before refinement survives the definition swap.
   *
   * SetInputDefinitions() migrates existing values onto swapped definitions,
   * so a bundle chosen before the entity type arrives is preserved.
   *
   * @covers ::setInputValue
   * @covers ::setInputDefinitions
   */
  public function testValueSetBeforeRefinementSurvives(): void {
    $tool = $this->toolManager->createInstance('refining_tool');
    $tool->setInputValue('bundle', 'article');
    $tool->setInputValue('entity_type', 'node');

    $this->assertSame('article', $tool->getInputValue('bundle'));
    // And the refined constraint now applies to it cleanly.
    $this->assertCount(0, $tool->validateInputValues(['bundle' => 'article']));
  }

  /**
   * Tests that refinement is isolated to the active tool instance.
   *
   * @covers ::getInputDefinitions
   * @covers ::setInputValue
   */
  public function testRefinementIsIsolatedAcrossInstances(): void {
    $first = $this->toolManager->createInstance('refining_tool');
    $second = $this->toolManager->createInstance('refining_tool');
    $plugin_definition = $this->toolManager->getDefinition('refining_tool');

    $first->setInputValue('entity_type', 'node');

    $this->assertArrayHasKey('Choice', $first->getInputDefinition('bundle')->getConstraints());
    $this->assertArrayNotHasKey('Choice', $second->getInputDefinition('bundle')->getConstraints());
    $this->assertArrayNotHasKey('Choice', $plugin_definition->getInputDefinition('bundle')->getConstraints());

    $third = $this->toolManager->createInstance('refining_tool');
    $this->assertArrayNotHasKey('Choice', $third->getInputDefinition('bundle')->getConstraints());
  }

  /**
   * Tests that nested input definitions are isolated across instances.
   *
   * @covers ::getInputDefinitions
   */
  public function testNestedDefinitionsAreIsolatedAcrossInstances(): void {
    $first = $this->toolManager->createInstance('comprehensive_types_tool');
    $second = $this->toolManager->createInstance('comprehensive_types_tool');
    $plugin_definition = $this->toolManager->getDefinition('comprehensive_types_tool');

    $first_map = $first->getInputDefinition('metadata');
    $this->assertInstanceOf(MapInputDefinition::class, $first_map);
    $first_list = $first_map->getPropertyDefinition('tags');
    $this->assertInstanceOf(ListInputDefinition::class, $first_list);
    $first_list->getItemDefinition()->addConstraint('NotBlank', []);

    $second_map = $second->getInputDefinition('metadata');
    $this->assertInstanceOf(MapInputDefinition::class, $second_map);
    $second_list = $second_map->getPropertyDefinition('tags');
    $this->assertInstanceOf(ListInputDefinition::class, $second_list);
    $this->assertArrayNotHasKey('NotBlank', $second_list->getItemDefinition()->getConstraints());

    $plugin_map = $plugin_definition->getInputDefinition('metadata');
    $this->assertInstanceOf(MapInputDefinition::class, $plugin_map);
    $plugin_list = $plugin_map->getPropertyDefinition('tags');
    $this->assertInstanceOf(ListInputDefinition::class, $plugin_list);
    $this->assertArrayNotHasKey('NotBlank', $plugin_list->getItemDefinition()->getConstraints());

    $third = $this->toolManager->createInstance('comprehensive_types_tool');
    $third_map = $third->getInputDefinition('metadata');
    $this->assertInstanceOf(MapInputDefinition::class, $third_map);
    $third_list = $third_map->getPropertyDefinition('tags');
    $this->assertInstanceOf(ListInputDefinition::class, $third_list);
    $this->assertArrayNotHasKey('NotBlank', $third_list->getItemDefinition()->getConstraints());
  }

  /**
   * Tests that a refiner waits for every dependency it declares.
   *
   * A refiner is dispatched for each of its dependencies in turn, so one
   * declaring several fires as soon as the first arrives. An optional
   * dependency validates while NULL, so nothing else stops the refiner being
   * handed values it was promised.
   *
   * @covers ::setInputValue
   * @covers ::refinerDependenciesResolved
   */
  public function testPartiallyResolvedDependenciesDeferRefinement(): void {
    $tool = $this->toolManager->createInstance('multi_dependency_refining_tool');

    // 'field' depends on entity_type and bundle; only the first is set here,
    // so the refiner must not run yet.
    $tool->setInputValue('entity_type', 'node');
    $this->assertArrayNotHasKey('Choice', $tool->getInputDefinition('field')->getConstraints());

    // With both dependencies resolved the refiner runs and narrows 'field'.
    $tool->setInputValue('bundle', 'article');
    $constraints = $tool->getInputDefinition('field')->getConstraints();
    $this->assertSame(['title', 'body'], $constraints['Choice']['choices']);
  }

  /**
   * Tests that the refiner runs whichever order its dependencies arrive in.
   *
   * @covers ::setInputValue
   * @covers ::refinerDependenciesResolved
   */
  public function testRefinementRunsRegardlessOfDependencyOrder(): void {
    $tool = $this->toolManager->createInstance('multi_dependency_refining_tool');

    $tool->setInputValue('bundle', 'tags');
    $this->assertArrayNotHasKey('Choice', $tool->getInputDefinition('field')->getConstraints());

    $tool->setInputValue('entity_type', 'taxonomy_term');
    $constraints = $tool->getInputDefinition('field')->getConstraints();
    $this->assertSame(['name'], $constraints['Choice']['choices']);
  }

  /**
   * Tests that clearing one dependency reverts the refinement.
   *
   * A refinement outlasting the value it was derived from would constrain
   * 'field' to a bundle that is no longer selected.
   *
   * @covers ::setInputValue
   * @covers ::refinerDependenciesResolved
   */
  public function testClearingOneDependencyRevertsRefinement(): void {
    $tool = $this->toolManager->createInstance('multi_dependency_refining_tool');

    $tool->setInputValue('entity_type', 'node');
    $tool->setInputValue('bundle', 'page');
    $constraints = $tool->getInputDefinition('field')->getConstraints();
    $this->assertSame(['title'], $constraints['Choice']['choices']);

    $tool->setInputValue('bundle', NULL);
    $this->assertArrayNotHasKey('Choice', $tool->getInputDefinition('field')->getConstraints());
  }

  /**
   * Tests a refiner's Choice on a multiple input binds each item.
   *
   * EntityList in tool_belt_content refines its multiple 'fields' input the
   * way this tool refines 'field': Choice lists the fields of an entity type
   * and bundle. A multiple input is converted to a list when it is declared
   * (#3583049), so the refiner's Choice lands on the list, where it would
   * bind the whole value. The refinement moves it to the item, where the
   * multiple definition put it.
   *
   * @covers ::setInputValue
   * @covers ::splitRefinedMultipleConstraints
   */
  #[IgnoreDeprecations]
  public function testRefinedChoiceBindsItemsOfConvertedMultiple(): void {
    $this->expectDeprecation("Declaring the 'field' input with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Declare a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $plugin_definition = $this->toolManager->getDefinition('multi_dependency_refining_tool');
    $plugin_definition->addInputDefinition('field', new InputDefinition(
      data_type: 'string',
      label: 'Fields',
      description: 'The fields, refined per entity type and bundle.',
      required: FALSE,
      multiple: TRUE,
    ));
    $tool = $this->toolManager->createInstance('multi_dependency_refining_tool');
    $tool->setInputValue('entity_type', 'node');
    $tool->setInputValue('bundle', 'article');

    $this->assertSame('', (string) $tool->validateInputValues(['field' => ['title']]));
    $violations = $tool->validateInputValues(['field' => ['title', 'uid']]);
    $this->assertCount(1, $violations);
    $this->assertSame('Input field: (item 1) The value you selected is not a valid choice.', strip_tags((string) $violations->get(0)->getMessage()));

    $schema = $this->container->get('tool.definition_serializer')->normalizeInputSchema($tool);
    $this->assertSame(['title', 'body'], $schema['properties']['field']['items']['enum'] ?? NULL);

    $refined = $tool->getInputDefinition('field');
    $this->assertInstanceOf(ListInputDefinition::class, $refined);
    // The refiner's constraints bind each item; nothing is implied for the
    // list, so [] stays a value and no minItems is published (#3583069).
    $this->assertSame([], $refined->getConstraints());
    $this->assertSame(['title', 'body'], $refined->getItemDefinition()->getConstraints()['Choice']['choices']);
    $this->assertArrayHasKey('NotBlank', $refined->getItemDefinition()->getConstraints());
    $this->assertCount(0, $tool->validateInputValues(['field' => []]));
    $violations = $tool->validateInputValues(['field' => ['title', '']]);
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = strip_tags((string) $violation->getMessage());
    }
    $this->assertContains('Input field: (item 1) This value should not be blank.', $messages);
    $this->assertArrayNotHasKey('minItems', (array) $schema['properties']['field']);
    $declared = $plugin_definition->getInputDefinition('field');
    $this->assertInstanceOf(ListInputDefinition::class, $declared);
    $this->assertArrayNotHasKey('Choice', $declared->getItemDefinition()->getConstraints(), 'The declared definition is untouched.');

    // Refining again starts from the declared definition, so the Choice
    // moves once and follows the new values.
    $tool->setInputValue('bundle', 'page');
    $refined = $tool->getInputDefinition('field');
    $this->assertInstanceOf(ListInputDefinition::class, $refined);
    $this->assertSame([], $refined->getConstraints());
    $this->assertSame(['title'], $refined->getItemDefinition()->getConstraints()['Choice']['choices']);
  }

  /**
   * Tests a refiner's NotBlank reaches the items of a NotNull-era multiple.
   *
   * The converted list of a multiple declared with NotNull carries no
   * list-level constraint, so a refiner's NotBlank is an addition and binds
   * each item (#3583069).
   *
   * @covers ::setInputValue
   * @covers ::splitRefinedMultipleConstraints
   */
  public function testRefinerNotBlankBindsItemsOfNotNullMultiple(): void {
    $this->toolManager->getDefinition('multi_dependency_refining_tool')->addInputDefinition('field', ListInputDefinition::fromMultiple(new InputDefinition(
      data_type: 'string',
      label: 'Fields',
      description: 'The fields, refined per entity type and bundle.',
      required: FALSE,
      multiple: TRUE,
      constraints: ['NotNull' => []],
    )));
    $tool = $this->toolManager->createInstance('multi_dependency_refining_tool');
    $tool->setInputValue('entity_type', 'node');
    $tool->setInputValue('bundle', 'article');

    $refined = $tool->getInputDefinition('field');
    $this->assertInstanceOf(ListInputDefinition::class, $refined);
    $this->assertSame([], $refined->getConstraints());
    $item_constraints = $refined->getItemDefinition()->getConstraints();
    $this->assertArrayHasKey('NotNull', $item_constraints);
    $this->assertArrayHasKey('NotBlank', $item_constraints);

    $messages = [];
    foreach ($tool->validateInputValues(['field' => ['title', '']]) as $violation) {
      $messages[] = strip_tags((string) $violation->getMessage());
    }
    $this->assertContains('Input field: (item 1) This value should not be blank.', $messages);
  }

  /**
   * Tests a refiner's new multiple definition converts to a list.
   *
   * A refiner written against the multiple definition may build its own
   * definition with multiple: TRUE and a Choice, as the create-tool-plugins
   * skill taught. The declared input is the list multiple: TRUE converts to
   * (#3583049), so the refined definition is converted the same way, with a
   * deprecation, before the narrowing check, and its Choice binds each item.
   *
   * @covers ::setInputValue
   */
  public function testRefinedMultipleDefinitionConvertsToList(): void {
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    // Capture the deprecation to pin that it fires once. Class loading can
    // report deprecations of its own here, so only this one is kept.
    $deprecations = [];
    set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
      if (str_starts_with($message, 'Refining ')) {
        $deprecations[] = $message;
      }
      return TRUE;
    }, E_USER_DEPRECATED);
    try {
      $tool->setInputValue('list_to_multiple_source', 'anything');
    }
    finally {
      restore_error_handler();
    }
    $this->assertSame(["Refining the 'list_to_multiple_target' input to a definition with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Return a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049"], $deprecations);

    $refined = $tool->getInputDefinition('list_to_multiple_target');
    $this->assertInstanceOf(ListInputDefinition::class, $refined);
    $this->assertSame([], $refined->getConstraints());
    $this->assertSame(['title', 'body'], $refined->getItemDefinition()->getConstraints()['Choice']['choices']);

    $this->assertSame('', (string) $tool->validateInputValues(['list_to_multiple_target' => ['title']]));
    $violations = $tool->validateInputValues(['list_to_multiple_target' => ['title', 'uid']]);
    $this->assertCount(1, $violations);
    $this->assertSame('Input list_to_multiple_target: (item 1) The value you selected is not a valid choice.', strip_tags((string) $violations->get(0)->getMessage()));

    $schema = $this->container->get('tool.definition_serializer')->normalizeInputSchema($tool);
    $this->assertSame(['title', 'body'], $schema['properties']['list_to_multiple_target']['items']['enum'] ?? NULL);
  }

  /**
   * Tests a refiner's constraint on a declared list stays on the list.
   *
   * Only a list converted from a multiple input has its refined constraints
   * moved to the item: a list declared as a list means its own constraints.
   *
   * @covers ::setInputValue
   * @covers ::splitRefinedMultipleConstraints
   */
  public function testRefinedConstraintStaysOnDeclaredList(): void {
    $this->toolManager->getDefinition('multi_dependency_refining_tool')->addInputDefinition('field', new ListInputDefinition(
      label: 'Fields',
      description: 'The fields, refined per entity type and bundle.',
      required: FALSE,
      item_definition: new InputDefinition('string', 'Field', 'A field.'),
    ));
    $tool = $this->toolManager->createInstance('multi_dependency_refining_tool');
    $tool->setInputValue('entity_type', 'node');
    $tool->setInputValue('bundle', 'article');

    $refined = $tool->getInputDefinition('field');
    $this->assertInstanceOf(ListInputDefinition::class, $refined);
    $this->assertSame(['title', 'body'], $refined->getConstraints()['Choice']['choices']);
    // On a declared list the refiner's constraints stay on the list: the
    // NotBlank binds the list itself and nothing moves to the item.
    $this->assertArrayHasKey('NotBlank', $refined->getConstraints());
    $this->assertSame([], $refined->getItemDefinition()->getConstraints());
  }

}
