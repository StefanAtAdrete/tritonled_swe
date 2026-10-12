<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolManager;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests enforcement of the refinement narrowing contract.
 *
 * The unrefined definition produces the schema invokers advertise, which is
 * static, so refinement must accept a subset of what the advertisement
 * accepts: constraints and deeper structure may be added, but the data type
 * and multiplicity are fixed — except that 'any' may refine to any concrete
 * type and an entity type may narrow to a sub-type.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class RefinerContractTest extends KernelTestBase {

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
   * Tests that a refinement changing the data type throws.
   */
  public function testTypeChangingRefinementThrows(): void {
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Refining the type_target input must not change its data type (string to integer)');
    $tool->setInputValue('type_source', 'anything');
  }

  /**
   * Tests that a refinement changing the multiplicity throws.
   *
   * A multiple definition a refiner returns converts to the list it means,
   * with a deprecation (#3583049), so refining a single input to one changes
   * its data type.
   */
  #[IgnoreDeprecations]
  public function testMultiplicityChangingRefinementThrows(): void {
    $this->expectDeprecation("Refining the 'multiple_target' input to a definition with multiple: TRUE is deprecated in tool:1.0.0-beta10 and is removed from tool:1.0.0-rc1. Return a ListInputDefinition instead. See https://git.drupalcode.org/project/tool/-/work_items/3583049");
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Refining the multiple_target input must not change its data type (string to list)');
    $tool->setInputValue('multiple_source', 'anything');
  }

  /**
   * Tests that refining 'any' to a concrete type is allowed and validates.
   *
   * The untyped advertisement accepts every value, so the concrete type
   * narrows validation only: a coercible value lands typed and an
   * unresolvable one is reported as a violation naming the input.
   */
  public function testAnyRefinementNarrowsValidationOnly(): void {
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    $tool->setInputValue('any_source', 'anything');
    $this->assertSame('integer', $tool->getInputDefinition('any_target')->getDataType());

    $tool->setInputValue('any_target', '5');
    $this->assertCount(0, $tool->validateInputs());
    $this->assertSame(5, $tool->getInputValue('any_target'));

    $tool2 = $this->toolManager->createInstance('refiner_contract_tool');
    $tool2->setInputValue('any_source', 'anything');
    $tool2->setInputValue('any_target', 'not-a-number');
    $violations = $tool2->validateInputs();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertStringContainsString('any_target', (string) $violations->get(0)->getMessage());
  }

  /**
   * Tests that narrowing an entity type to a sub-type is allowed.
   */
  public function testEntitySubTypeRefinementAllowed(): void {
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    $tool->setInputValue('entity_source', 'anything');
    $this->assertSame('entity:node', $tool->getInputDefinition('entity_target')->getDataType());
  }

  /**
   * Tests that constraint-only refinement keeps working unchanged.
   */
  public function testConstraintOnlyRefinementUnaffected(): void {
    $tool = $this->toolManager->createInstance('refining_tool');
    $tool->setInputValue('entity_type', 'node');
    $constraints = $tool->getInputDefinition('bundle')->getConstraints();
    $this->assertArrayHasKey('Choice', $constraints);
  }

  /**
   * Tests that a list of maps refined to a list of the same type is allowed.
   *
   * The target was a multiple map, which is now declared as the list of
   * maps it converts to (#3583049). A refiner may replace it with a
   * ListInputDefinition whose item type matches or narrows the original's
   * item type. This is valid narrowing.
   */
  public function testMultipleMapToListOfMapsAllowed(): void {
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    $tool->setInputValue('multi_to_list_source', 'anything');
    $refined = $tool->getInputDefinition('multi_to_list_target');
    $this->assertSame('list', $refined->getDataType());
  }

  /**
   * Tests that a list of maps refined to a list of strings throws.
   *
   * When the item data type of the refined list does not match or narrow the
   * original's item type (map → string), the narrowing contract is violated
   * and a LogicException must be thrown.
   */
  public function testMultipleMapToListOfStringsThrows(): void {
    $tool = $this->toolManager->createInstance('refiner_contract_tool');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Refining the multi_to_list_bad_target input must not change its item data type (map to string)');
    $tool->setInputValue('multi_to_list_bad_source', 'anything');
  }

}
