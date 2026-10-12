<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests map and list inputs advertise fully through the bridge.
 *
 * Guards the removal of the ai_tools_property_alter hook implementation:
 * that hook expanded one level of map properties, only fired on the ai
 * module's default normalize path, and never handled lists or nesting.
 * The bridge's own normalize() builds properties from the emitted JSON
 * Schema, so every shape below must advertise completely without it.
 *
 * @group tool_ai_connector
 */
#[Group('tool_ai_connector')]
#[RunTestsInSeparateProcesses]
final class MapListAdvertisementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'serialization',
    'node',
    'field',
    'text',
    'ai',
    'tool',
    'tool_ai_connector',
    'tool_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // comprehensive_types_tool declares entity:node inputs.
    $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create(['type' => 'article', 'name' => 'Article'])
      ->save();
  }

  /**
   * Renders the advertised parameters for a bridged tool.
   *
   * @param string $plugin_id
   *   The function call plugin ID.
   *
   * @return array
   *   The rendered parameters schema.
   */
  protected function renderParameters(string $plugin_id): array {
    $function_call = $this->container->get('plugin.manager.ai.function_calls')->createInstance($plugin_id);
    return $function_call->normalize()->renderFunctionArray()['parameters'];
  }

  /**
   * Tests a map input advertises its nested properties at every depth.
   */
  public function testMapInputAdvertisesNestedProperties(): void {
    $parameters = $this->renderParameters('tool:complex_event_tool');

    $payload = $parameters['properties']['payload'];
    $this->assertSame('object', $payload['type']);
    $this->assertArrayHasKey('title', $payload['properties']);
    $this->assertSame('string', $payload['properties']['title']['type']);

    // The removed hook expanded only one level; nesting must survive too.
    $profile = $payload['properties']['profile'];
    $this->assertSame('object', $profile['type']);
    $preferences = $profile['properties']['preferences'];
    $this->assertSame('object', $preferences['type']);
    $this->assertSame('string', $preferences['properties']['channel']['type']);
    $this->assertSame('array', $preferences['properties']['tags']['type']);
  }

  /**
   * Tests list and free-form map inputs advertise their shapes.
   *
   * The removed hook never covered either: a multiple input must advertise
   * an array with an item schema, and a map without declared properties
   * must advertise an object rather than an empty or untyped schema.
   */
  public function testListAndFreeFormMapInputsAdvertise(): void {
    $parameters = $this->renderParameters('tool:comprehensive_types_tool');

    $labels = $parameters['properties']['labels'];
    $this->assertSame('array', $labels['type']);
    $this->assertSame('string', $labels['items']['type']);

    $attributes = $parameters['properties']['attributes'];
    $this->assertSame('object', $attributes['type']);
  }

}
