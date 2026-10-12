<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Normalizer;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Normalizer\ToolDefinitionSerializer;
use Drupal\tool\Tool\ToolManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the complete output schema a tool publishes.
 *
 * @group tool
 */
#[Group('tool')]
#[RunTestsInSeparateProcesses]
final class OutputSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'serialization', 'tool', 'tool_test'];

  /**
   * The tool definition serializer.
   */
  private ToolDefinitionSerializer $serializer;

  /**
   * The tool plugin manager.
   */
  private ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->serializer = $this->container->get('tool.definition_serializer');
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * The output schema wraps the definitions and lists the required outputs.
   */
  public function testOutputSchemaWrapsDefinitions(): void {
    $tool = $this->toolManager->createInstance('extra_output_tool');
    $schema = $this->serializer->normalizeOutputSchema($tool);

    $this->assertSame('object', $schema['type']);
    $this->assertSame($this->serializer->normalizeOutputDefinitions($tool), $schema['properties']);
    $this->assertSame(['declared', 'optional_note'], array_keys($schema['properties']));
    $this->assertSame(['declared'], $schema['required'], 'An output declared optional is not listed as required.');
    $this->assertArrayNotHasKey('additionalProperties', $schema);

    $encoded = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), TRUE);
    $this->assertSame(['declared'], $encoded['required']);
    $this->assertSame('string', $encoded['properties']['declared']['type']);
  }

  /**
   * A tool without outputs advertises an object with no properties.
   */
  public function testOutputSchemaForToolWithoutOutputs(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $schema = $this->serializer->normalizeOutputSchema($tool);

    $this->assertArrayNotHasKey('required', $schema);
    $this->assertSame('{"type":"object","properties":{}}', json_encode($schema, JSON_THROW_ON_ERROR), 'An empty properties map encodes as an object, not an array.');
  }

}
