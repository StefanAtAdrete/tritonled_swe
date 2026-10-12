<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Tool\ToolManager;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests map validation with an omitted optional entity property.
 *
 * Regression test for #3583004: validateInputs() crashes when a map
 * omits an optional entity property. Core's
 * EntityUntranslatableFieldsConstraintValidator dereferences a NULL
 * entity without a guard when the typed-data system instantiates every
 * declared property — including absent optional ones.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedInputsTrait
 */
#[RunTestsInSeparateProcesses]
class MapOptionalEntityValidationTest extends KernelTestBase {

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
   * The tool plugin manager.
   */
  protected ToolManager $toolManager;

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
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests validation does not crash on absent optional entity property.
   *
   * The comprehensive_types_tool declares a 'metadata' map input with
   * an optional 'related_entity' property (EntityInputDefinition).
   * Passing only {'label': 'sample'} — a schema-valid value — must not
   * crash.
   *
   * @covers ::validateInputValue
   */
  public function testOmittedOptionalEntityPropertyDoesNotFatal(): void {
    $tool = $this->toolManager->createInstance('comprehensive_types_tool');

    // Supply only the required 'label' property; omit optional
    // 'related_entity' and 'tags'.
    $violations = $tool->validateInputValues([
      'metadata' => ['label' => 'sample'],
    ]);

    $this->assertCount(
      0,
      $violations,
      'Omitting an optional entity property must not crash.'
    );
  }

  /**
   * Tests required entity properties produce violations when absent.
   *
   * If a map declared a *required* entity property and it was absent,
   * that should still be caught by validation (not crash, not silently
   * pass).
   *
   * @covers ::validateInputValue
   */
  public function testRequiredEntityPropertyStillValidated(): void {
    $tool = $this->toolManager->createInstance('comprehensive_types_tool');

    // The top-level 'entity' input is required. Setting only metadata
    // should still produce a violation for the missing entity.
    $tool->setInputValue('name', 'test');
    $tool->setInputValue('count', 1);
    $tool->setInputValue('metadata', ['label' => 'sample']);
    // Do not set 'entity' — it's required.
    $violations = $tool->validateInputs();
    $found_entity_violation = FALSE;
    foreach ($violations as $violation) {
      if (str_contains((string) $violation->getMessage(), 'entity')) {
        $found_entity_violation = TRUE;
        break;
      }
    }
    $this->assertTrue(
      $found_entity_violation,
      'A required entity input must produce a violation when absent.'
    );
  }

}
