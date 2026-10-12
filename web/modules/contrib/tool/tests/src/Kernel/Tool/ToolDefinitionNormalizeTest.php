<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Event\ToolInputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool_test\EventSubscriber\TestToolTransformSubscriber;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests invoker-aware definition normalization via ToolDefinitionSerializer.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolDefinitionNormalizeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
    'serialization',
  ];

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected $toolManager;

  /**
   * The tool definition serializer.
   *
   * @var \Drupal\tool\Normalizer\ToolDefinitionSerializer
   */
  protected $definitionSerializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
    $this->definitionSerializer = $this->container->get('tool.definition_serializer');
  }

  /**
   * Tests input definitions are not transformed when no invoker is set.
   */
  public function testInputDefinitionsWithoutInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');

    $normalized = $this->definitionSerializer->normalizeInputDefinitions($tool);

    $this->assertArrayHasKey('name', $normalized);
    // Without invoker, the definition should remain as-is (string type).
    $this->assertEquals('string', $normalized['name']['type']);
    // Description should come from the original definition.
    $this->assertStringContainsString('Name', $normalized['name']['description']);
  }

  /**
   * Tests that input definitions are transformed when invoker is set.
   *
   * The test subscriber replaces the 'name' input definition with one
   * describing an email address.
   */
  public function testInputDefinitionsWithInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);

    $normalized = $this->definitionSerializer->normalizeInputDefinitions($tool);

    $this->assertArrayHasKey('name', $normalized);
    // The subscriber should have replaced the definition.
    $this->assertStringContainsString('email', strtolower($normalized['name']['description']));
  }

  /**
   * Tests that output definitions are transformed when invoker is set.
   *
   * The test subscriber replaces the 'message' output definition with one
   * describing a token format.
   */
  public function testOutputDefinitionsWithInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], TestToolTransformSubscriber::INVOKER_ID);

    $normalized = $this->definitionSerializer->normalizeOutputDefinitions($tool);

    $this->assertArrayHasKey('message', $normalized);
    // The subscriber should have replaced the definition.
    $this->assertStringContainsString('token', strtolower($normalized['message']['description']));
  }

  /**
   * Tests that output definitions are NOT transformed for a different invoker.
   */
  public function testOutputDefinitionsWithDifferentInvoker(): void {
    $tool = $this->toolManager->createInstance('greeting_tool', [], 'some_other_invoker');

    $normalized = $this->definitionSerializer->normalizeOutputDefinitions($tool);

    $this->assertArrayHasKey('message', $normalized);
    // Should remain as original — no token description.
    $this->assertStringContainsString('Message', $normalized['message']['description']);
  }

  /**
   * Tests that the correct event type is dispatched for inputs.
   */
  public function testEventDirectionForInputs(): void {
    $dispatched = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputDefinitionNormalizeEvent::class,
      function (ToolInputDefinitionNormalizeEvent $event) use (&$dispatched) {
        $dispatched[] = $event->getName();
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'test_direction');
    $this->definitionSerializer->normalizeInputDefinitions($tool);

    // Should have dispatched for input definitions.
    $this->assertNotEmpty($dispatched);
    $this->assertContains('name', $dispatched);
  }

  /**
   * Tests that the correct event type is dispatched for outputs.
   */
  public function testEventDirectionForOutputs(): void {
    $dispatched = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputDefinitionNormalizeEvent::class,
      function (ToolOutputDefinitionNormalizeEvent $event) use (&$dispatched) {
        $dispatched[] = $event->getName();
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'test_direction');
    $this->definitionSerializer->normalizeOutputDefinitions($tool);

    // Should have dispatched for output definitions.
    $this->assertNotEmpty($dispatched);
    $this->assertContains('message', $dispatched);
  }

  /**
   * Tests that the event carries the correct tool ID and definition name.
   */
  public function testEventCarriesCorrectContext(): void {
    $events = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputDefinitionNormalizeEvent::class,
      function (ToolInputDefinitionNormalizeEvent $event) use (&$events) {
        $events[] = [
          'name' => $event->getName(),
          'tool_id' => $event->getToolId(),
          'invoker' => $event->getInvoker()?->id,
        ];
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool', [], 'my_invoker');
    $this->definitionSerializer->normalizeInputDefinitions($tool);

    $this->assertNotEmpty($events);
    // Should have events for 'name' and 'greeting' inputs.
    $names = array_column($events, 'name');
    $this->assertContains('name', $names);
    $this->assertContains('greeting', $names);

    // All should carry the correct tool ID and invoker.
    foreach ($events as $event) {
      $this->assertEquals('greeting_tool', $event['tool_id']);
      $this->assertEquals('my_invoker', $event['invoker']);
    }
  }

  /**
   * Tests that definitions without invoker context are not dispatched.
   *
   * When no invoker is set on the tool, the normalizer context won't have
   * the required fields, so no event should fire.
   */
  public function testNoEventWithoutInvoker(): void {
    $eventCount = 0;

    $this->container->get('event_dispatcher')->addListener(
      ToolInputDefinitionNormalizeEvent::class,
      function () use (&$eventCount) {
        $eventCount++;
      }
    );

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputDefinitionNormalizeEvent::class,
      function () use (&$eventCount) {
        $eventCount++;
      }
    );

    $tool = $this->toolManager->createInstance('greeting_tool');
    $this->definitionSerializer->normalizeInputDefinitions($tool);
    $this->definitionSerializer->normalizeOutputDefinitions($tool);

    // No invoker = no events dispatched (context check fails).
    $this->assertEquals(0, $eventCount);
  }

  /**
   * Tests that events fire for nested properties within a MapDefinition.
   *
   * Uses the optional_map_tool which has a 'settings' map input containing
   * 'color' and 'enabled' child properties. Verifies a subscriber can
   * target and swap a nested child definition.
   */
  public function testNestedDefinitionTransform(): void {
    $dispatched = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputDefinitionNormalizeEvent::class,
      function (ToolInputDefinitionNormalizeEvent $event) use (&$dispatched) {
        $dispatched[] = $event->getName();
        // Swap the nested 'color' property to an 'integer' type.
        if ($event->getName() === 'color') {
          $event->setDefinition(new InputDefinition(
            data_type: 'integer',
            label: new TranslatableMarkup('Color Code'),
            description: new TranslatableMarkup('Numeric color code.'),
            required: FALSE,
          ));
        }
      }
    );

    $tool = $this->toolManager->createInstance(
      'optional_map_tool',
      [],
      'nested_test_invoker'
    );
    $normalized = $this->definitionSerializer->normalizeInputDefinitions($tool);

    // The event should have fired for nested properties.
    $this->assertContains('color', $dispatched);
    $this->assertContains('enabled', $dispatched);

    // The 'settings' map should contain the transformed 'color' property.
    $this->assertArrayHasKey('settings', $normalized);
    $this->assertArrayHasKey('properties', $normalized['settings']);
    $this->assertArrayHasKey('color', $normalized['settings']['properties']);

    // Verify the swap took effect: the color schema should differ from
    // what a plain 'string' type would produce. The original 'color' was
    // a string InputDefinition, and we swapped it to integer.
    $colorSchema = $normalized['settings']['properties']['color'];
    // A string type would produce ['type' => 'string'] or
    // ['type' => ['string', 'null']]. After swapping to integer, it
    // should not contain 'string' as the type.
    if (isset($colorSchema['type'])) {
      if (is_array($colorSchema['type'])) {
        $this->assertNotContains('string', $colorSchema['type']);
      }
      else {
        $this->assertNotEquals('string', $colorSchema['type']);
      }
    }
    // If the description changed, that also proves the swap happened.
    if (isset($colorSchema['description'])) {
      $this->assertStringContainsString('color code', strtolower($colorSchema['description']));
    }
  }

}
