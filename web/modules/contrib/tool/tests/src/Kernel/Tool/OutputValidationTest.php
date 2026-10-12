<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\FailureCategory;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that execute() validates outputs by shape, the same way as inputs.
 *
 * An entity output only has to be an entity. Its fields are not validated,
 * because a tool that returns an entity it loaded must not fail for data it
 * did not write.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
final class OutputValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'node',
    'user',
    'system',
    'field',
    'text',
  ];

  /**
   * A node that fails entity validation.
   */
  private Node $invalidNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // A node without a title violates the title field's NotNull constraint,
    // so full entity validation rejects it while a shape check accepts it.
    $this->invalidNode = Node::create(['type' => 'article']);
    $this->assertGreaterThan(0, $this->invalidNode->validate()->count());
  }

  /**
   * An entity that fails its own validation still passes as an output.
   */
  public function testInvalidEntityPassesAsOutput(): void {
    $tool = $this->execute('entity_output_tool', [
      'entity' => $this->invalidNode,
      'mode' => 'passthrough',
    ]);

    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());
    $this->assertSame($this->invalidNode, $tool->getOutputValue('entity'));
  }

  /**
   * An entity nested in a map output is also checked by shape only.
   */
  public function testInvalidEntityNestedInMapOutputPasses(): void {
    $tool = $this->execute('comprehensive_types_tool', [
      'name' => 'shape',
      'count' => 1,
      'entity' => Node::create(['type' => 'article', 'title' => 'Separate']),
      'metadata' => [
        'label' => 'nested',
        'related_entity' => $this->invalidNode,
      ],
    ]);

    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());
    $this->assertSame($this->invalidNode, $tool->getOutputValue('result_map')['nested_entity']);
  }

  /**
   * A config entity passes as an entity output.
   *
   * Core derives entity:TYPE data types for config entity types too, so an
   * entity output is not limited to content entities.
   */
  public function testConfigEntityPassesAsOutput(): void {
    $tool = $this->execute('entity_output_tool', [
      'entity' => $this->invalidNode,
      'mode' => 'config_entity',
    ]);

    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());
    $this->assertSame('reviewer', $tool->getOutputValue('role')->id());
  }

  /**
   * A non-entity value in an entity output fails as a tool bug.
   */
  public function testNonEntityValueFails(): void {
    $tool = $this->execute('entity_output_tool', [
      'entity' => $this->invalidNode,
      'mode' => 'string',
    ]);

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Output entity: The Entity output must be an entity.', (string) $result->getMessage());
    // The typed-data root class stays in the log, not the message
    // (#3583057).
    $this->assertStringNotContainsString('Object(', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * A bad item in a multiple entity output is reported with its position.
   */
  public function testNonEntityListItemIsNamed(): void {
    $tool = $this->execute('entity_output_tool', [
      'entity' => $this->invalidNode,
      'mode' => 'mixed_list',
    ]);

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Output entities: (item 1) The Entities output must be an entity.', (string) $result->getMessage());
  }

  /**
   * A bad item in a list converted from a multiple output names the output.
   *
   * A multiple core output converts to a list whose item carries no label
   * (#3583049), so the message names the list instead, as it named the
   * multiple output.
   */
  public function testNonEntityConvertedListItemIsNamed(): void {
    $entities = OutputDefinition::fromContextDefinition(new EntityContextDefinition(
      data_type: 'node',
      label: 'Entities',
      required: FALSE,
      multiple: TRUE,
      description: 'The node that was passed in, as a list.',
    ), 'entities');
    $this->assertInstanceOf(ListOutputDefinition::class, $entities);
    $this->assertSame('', (string) $entities->getItemDefinition()->getLabel());
    $this->container->get('plugin.manager.tool')->getDefinition('entity_output_tool')->addOutputDefinition('entities', $entities);

    $tool = $this->execute('entity_output_tool', [
      'entity' => $this->invalidNode,
      'mode' => 'mixed_list',
    ]);

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Output entities: (item 1) The Entities output must be an entity.', (string) $result->getMessage());
  }

  /**
   * A NULL item in an optional multiple output is reported with its position.
   */
  public function testNullListItemIsNamed(): void {
    $tool = $this->execute('entity_output_tool', [
      'entity' => $this->invalidNode,
      'mode' => 'null_item',
    ]);

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Output entities: (item 0)', (string) $result->getMessage());
  }

  /**
   * Creates a tool, sets its inputs, and executes it.
   *
   * @param string $plugin_id
   *   The tool plugin ID.
   * @param array<string, mixed> $inputs
   *   Input values keyed by input name.
   */
  private function execute(string $plugin_id, array $inputs): ToolInterface {
    $tool = $this->container->get('plugin.manager.tool')->createInstance($plugin_id);
    $this->assertInstanceOf(ToolInterface::class, $tool);
    foreach ($inputs as $name => $value) {
      $tool->setInputValue($name, $value);
    }
    $tool->execute();
    return $tool;
  }

}
