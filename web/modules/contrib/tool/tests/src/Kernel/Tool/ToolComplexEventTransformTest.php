<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Event\ToolInputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\Handle\ToolHandleStore;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\ListInputDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\MapInputDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests event transforms on complex nested tool definitions and values.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class ToolComplexEventTransformTest extends KernelTestBase {

  /**
   * The invoker ID used for these tests.
   */
  protected const INVOKER_ID = 'complex_event_invoker';

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
   * The handle store.
   *
   * @var \Drupal\tool\Handle\ToolHandleStore
   */
  protected ToolHandleStore $handleStore;

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
    $this->definitionSerializer = $this->container->get('tool.definition_serializer');
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Tests definition events can transform deeply nested schema nodes.
   */
  public function testComplexDefinitionEventsTransformNestedSchemas(): void {
    $inputEvents = [];
    $outputEvents = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputDefinitionNormalizeEvent::class,
      function (ToolInputDefinitionNormalizeEvent $event) use (&$inputEvents): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $inputEvents[] = $event->getName();
        if ($event->getName() === 'alias') {
          $event->setDefinition(new InputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('Alias Handle'),
            description: new TranslatableMarkup('Deep alias handle accepted.'),
          ));
        }
        elseif ($event->getName() === 'note') {
          $event->setDefinition(new InputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('Note Token'),
            description: new TranslatableMarkup('Deep item note token accepted.'),
            required: FALSE,
          ));
        }
      }
    );

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputDefinitionNormalizeEvent::class,
      function (ToolOutputDefinitionNormalizeEvent $event) use (&$outputEvents): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $outputEvents[] = $event->getName();
        if ($event->getName() === 'channel') {
          $event->setDefinition(new OutputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('Channel Token'),
            description: new TranslatableMarkup('Deep channel token returned.'),
          ));
        }
        elseif ($event->getName() === 'label') {
          $event->setDefinition(new OutputDefinition(
            data_type: 'string',
            label: new TranslatableMarkup('Label Token'),
            description: new TranslatableMarkup('Nested item label token returned.'),
          ));
        }
      }
    );

    $tool = $this->toolManager->createInstance('complex_event_tool', [], static::INVOKER_ID);
    $inputSchema = $this->definitionSerializer->normalizeInputDefinitions($tool);
    $outputSchema = $this->definitionSerializer->normalizeOutputDefinitions($tool);

    $this->assertContains('payload', $inputEvents);
    $this->assertContains('profile', $inputEvents);
    $this->assertContains('preferences', $inputEvents);
    $this->assertContains('alias', $inputEvents);
    $this->assertContains('note', $inputEvents);

    $this->assertContains('report', $outputEvents);
    $this->assertContains('profile', $outputEvents);
    $this->assertContains('preferences', $outputEvents);
    $this->assertContains('channel', $outputEvents);
    $this->assertContains('label', $outputEvents);

    $aliasSchema = $inputSchema['payload']['properties']['profile']['properties']['alias'];
    $this->assertStringContainsString('alias handle', strtolower($aliasSchema['description']));

    $noteSchema = $inputSchema['payload']['properties']['items']['items']['properties']['metadata']['properties']['note'];
    $this->assertStringContainsString('note token', strtolower($noteSchema['description']));

    $channelSchema = $outputSchema['report']['properties']['profile']['properties']['preferences']['properties']['channel'];
    $this->assertStringContainsString('channel token', strtolower($channelSchema['description']));

    $labelSchema = $outputSchema['report']['properties']['items']['items']['properties']['label'];
    $this->assertStringContainsString('label token', strtolower($labelSchema['description']));
  }

  /**
   * Tests nested value events transform input, execution, and formatted output.
   */
  public function testComplexValueEventsTransformNestedValuesEndToEnd(): void {
    $inputEvents = [];
    $outputEvents = [];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputTransformEvent::class,
      function (ToolInputTransformEvent $event) use (&$inputEvents): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $inputEvents[] = $event->getInputName();
        $value = $event->getValue();
        if (!is_string($value)) {
          return;
        }

        $prefixes = [
          'title' => 'input-title:',
          'alias' => 'input-alias:',
          'channel' => 'input-channel:',
          'label' => 'input-label:',
          'note' => 'input-note:',
        ];
        if (isset($prefixes[$event->getInputName()])) {
          $event->setValue($prefixes[$event->getInputName()] . $value);
        }
      }
    );

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) use (&$outputEvents): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $outputEvents[] = $event->getOutputName();
        $value = $event->getValue();
        if (!is_string($value)) {
          return;
        }

        $prefixes = [
          'summary' => 'output-summary:',
          'alias' => 'output-alias:',
          'channel' => 'output-channel:',
          'label' => 'output-label:',
          'note' => 'output-note:',
        ];
        if (isset($prefixes[$event->getOutputName()])) {
          $event->setValue($prefixes[$event->getOutputName()] . $value);
          $event->addMessage('Transformed ' . $event->getOutputName() . '.');
        }
      }
    );

    $tool = $this->toolManager->createInstance('complex_event_tool', [], static::INVOKER_ID);
    $tool->setInputValue('payload', [
      'title' => 'Launch',
      'profile' => [
        'alias' => 'Ada',
        'preferences' => [
          'channel' => 'email',
          'tags' => ['alpha', 'beta'],
        ],
      ],
      'items' => [
        [
          'label' => 'First',
          'metadata' => [
            'note' => 'one',
          ],
        ],
        [
          'label' => 'Second',
          'metadata' => [
            'note' => 'two',
          ],
        ],
      ],
    ]);
    $tool->execute();
    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());

    $rawReport = $tool->getResult()->getContextValues()['report'];
    $this->assertSame('input-title:Launch / input-alias:Ada', $rawReport['summary']);
    $this->assertSame('input-alias:Ada', $rawReport['profile']['alias']);
    $this->assertSame('input-channel:email', $rawReport['profile']['preferences']['channel']);
    $this->assertSame('input-label:First', $rawReport['items'][0]['label']);
    $this->assertSame('input-note:one', $rawReport['items'][0]['metadata']['note']);
    $this->assertSame('input-label:Second', $rawReport['items'][1]['label']);
    $this->assertSame('input-note:two', $rawReport['items'][1]['metadata']['note']);

    $formatted = $tool->getFormattedResult();
    $formattedReport = $formatted->getContextValues()['report'];
    $this->assertSame('output-summary:input-title:Launch / input-alias:Ada', $formattedReport['summary']);
    $this->assertSame('output-alias:input-alias:Ada', $formattedReport['profile']['alias']);
    $this->assertSame('output-channel:input-channel:email', $formattedReport['profile']['preferences']['channel']);
    $this->assertSame('output-label:input-label:First', $formattedReport['items'][0]['label']);
    $this->assertSame('output-note:input-note:one', $formattedReport['items'][0]['metadata']['note']);
    $this->assertSame('output-label:input-label:Second', $formattedReport['items'][1]['label']);
    $this->assertSame('output-note:input-note:two', $formattedReport['items'][1]['metadata']['note']);

    $this->assertContains('title', $inputEvents);
    $this->assertContains('alias', $inputEvents);
    $this->assertContains('channel', $inputEvents);
    $this->assertCount(2, array_filter($inputEvents, fn (string $name): bool => $name === 'label'));
    $this->assertCount(2, array_filter($inputEvents, fn (string $name): bool => $name === 'note'));

    $this->assertContains('summary', $outputEvents);
    $this->assertContains('alias', $outputEvents);
    $this->assertContains('channel', $outputEvents);
    $this->assertCount(2, array_filter($outputEvents, fn (string $name): bool => $name === 'label'));
    $this->assertCount(2, array_filter($outputEvents, fn (string $name): bool => $name === 'note'));

    $this->assertContains('Transformed summary.', $formatted->getHints());
    $this->assertContains('Transformed note.', $formatted->getHints());
  }

  /**
   * Tests nested entity handles transform through complex values end-to-end.
   */
  public function testNestedEntityHandlesTransformThroughComplexValuesEndToEnd(): void {
    $featured = Node::create([
      'type' => 'article',
      'title' => 'Featured entity input',
    ]);
    $featured->save();
    $related = Node::create([
      'type' => 'article',
      'title' => 'Related entity input',
    ]);
    $related->save();

    $featured_handle = $this->handleStore->store($featured);
    $related_handle = $this->handleStore->store($related);

    $tool = $this->toolManager->createInstance('complex_entity_event_tool', [], new Invoker('tool_test', InvokerCapability::EntitiesAsHandles));
    $inputSchema = $this->definitionSerializer->normalizeInputDefinitions($tool);
    $outputSchema = $this->definitionSerializer->normalizeOutputDefinitions($tool);

    $featuredInputSchema = $inputSchema['payload']['properties']['profile']['properties']['featured_entity'];
    $this->assertSame('string', $featuredInputSchema['type']);
    $this->assertSame('Featured entity: A featured profile entity. (pass a handle from a previous tool call)', $featuredInputSchema['description']);

    $relatedOutputSchema = $outputSchema['report']['properties']['items']['items']['properties']['metadata']['properties']['related_entity'];
    $this->assertSame('string', $relatedOutputSchema['type']);
    $this->assertSame('Related entity: A related item entity. (returns a handle for use in subsequent tool calls)', $relatedOutputSchema['description']);

    $tool->setInputValue('payload', [
      'title' => 'Launch',
      'profile' => [
        'alias' => 'Ada',
        'featured_entity' => $featured_handle,
      ],
      'items' => [
        [
          'label' => 'First',
          'metadata' => [
            'related_entity' => $related_handle,
          ],
        ],
      ],
    ]);
    $tool->execute();
    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());

    $rawReport = $tool->getResult()->getContextValues()['report'];
    $this->assertInstanceOf(Node::class, $rawReport['profile']['featured_entity']);
    $this->assertInstanceOf(Node::class, $rawReport['items'][0]['metadata']['related_entity']);
    $this->assertSame('Featured entity returned', $rawReport['profile']['featured_entity']->getTitle());
    $this->assertSame('Related entity returned 1', $rawReport['items'][0]['metadata']['related_entity']->getTitle());

    $formatted = $tool->getFormattedResult();
    $formattedReport = $formatted->getContextValues()['report'];
    $this->assertSame($featured_handle, $formattedReport['profile']['featured_entity']);
    $this->assertSame($related_handle, $formattedReport['items'][0]['metadata']['related_entity']);

    $this->assertSame('Featured entity returned', $this->handleStore->resolve($featured_handle)->getTitle());
    $this->assertSame('Related entity returned 1', $this->handleStore->resolve($related_handle)->getTitle());
  }

  /**
   * Tests scalar subscribers work through multiple, list, and map structures.
   */
  public function testScalarTransformSubscribersHandleRecursiveStructures(): void {
    $inputEvents = [];
    $outputEvents = [];
    $inputNames = [
      'multiple_numbers',
      'number_list',
      'map_number',
      'multiple_number_lists',
    ];
    $outputNames = [
      'multiple_numbers',
      'number_list',
      'map_number',
      'multiple_number_lists',
    ];

    $this->container->get('event_dispatcher')->addListener(
      ToolInputDefinitionNormalizeEvent::class,
      function (ToolInputDefinitionNormalizeEvent $event) use ($inputNames): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $definition = $event->getDefinition();
        if ($definition->getDataType() === 'integer' && in_array($event->getName(), $inputNames, TRUE)) {
          $event->setDefinition(new InputDefinition(
            data_type: 'string',
            label: $definition->getLabel() ?: new TranslatableMarkup('String value'),
            description: new TranslatableMarkup('A string value that is upcast to an integer.'),
            required: $definition->isRequired(),
            multiple: $definition->isMultiple(),
          ));
        }
      }
    );

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputDefinitionNormalizeEvent::class,
      function (ToolOutputDefinitionNormalizeEvent $event) use ($outputNames): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $definition = $event->getDefinition();
        if ($definition->getDataType() === 'integer' && in_array($event->getName(), $outputNames, TRUE)) {
          $event->setDefinition(new OutputDefinition(
            data_type: 'string',
            label: $definition->getLabel() ?: new TranslatableMarkup('String value'),
            required: $definition->isRequired(),
            multiple: $definition->isMultiple(),
            description: new TranslatableMarkup('A string value downcast from an integer.'),
          ));
        }
      }
    );

    $this->container->get('event_dispatcher')->addListener(
      ToolInputTransformEvent::class,
      function (ToolInputTransformEvent $event) use (&$inputEvents, $inputNames): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $value = $event->getValue();
        $inputEvents[] = [
          'name' => $event->getInputName(),
          'is_scalar_target' => is_string($value) && in_array($event->getInputName(), $inputNames, TRUE),
        ];

        if (is_string($value) && in_array($event->getInputName(), $inputNames, TRUE)) {
          $event->setValue((int) $value);
        }
      },
      // Invoker-specific transforms run above the generic type coercion
      // subscriber (50), which would otherwise coerce the numeric strings
      // before this listener sees them.
      100,
    );

    $this->container->get('event_dispatcher')->addListener(
      ToolOutputTransformEvent::class,
      function (ToolOutputTransformEvent $event) use (&$outputEvents, $outputNames): void {
        if ($event->getInvoker()?->id !== static::INVOKER_ID) {
          return;
        }

        $value = $event->getValue();
        $outputEvents[] = [
          'name' => $event->getOutputName(),
          'is_scalar_target' => is_int($value) && in_array($event->getOutputName(), $outputNames, TRUE),
        ];

        if (is_int($value) && in_array($event->getOutputName(), $outputNames, TRUE)) {
          $event->setValue((string) $value);
        }
      }
    );

    $tool = $this->toolManager->createInstance('recursive_scalar_transform_tool', [], static::INVOKER_ID);
    // The multiple_numbers input is declared as the list that a multiple
    // input converts to (#3583049).
    $multipleInputDefinition = $tool->getInputDefinition('multiple_numbers');
    $this->assertInstanceOf(ListInputDefinition::class, $multipleInputDefinition);
    $this->assertFalse($multipleInputDefinition->isMultiple());
    $this->assertSame('integer', $multipleInputDefinition->getItemDefinition()->getDataType());

    $listInputDefinition = $tool->getInputDefinition('number_list');
    $this->assertInstanceOf(ListInputDefinition::class, $listInputDefinition);
    $this->assertFalse($listInputDefinition->isMultiple());
    $this->assertSame('integer', $listInputDefinition->getItemDefinition()->getDataType());

    $mapInputDefinition = $tool->getInputDefinition('number_map');
    $this->assertInstanceOf(MapInputDefinition::class, $mapInputDefinition);
    $this->assertSame('integer', $mapInputDefinition->getPropertyDefinition('map_number')->getDataType());

    $multipleListInputDefinition = $tool->getInputDefinition('multiple_number_lists');
    $this->assertInstanceOf(ListInputDefinition::class, $multipleListInputDefinition);
    $innerListInputDefinition = $multipleListInputDefinition->getItemDefinition();
    $this->assertInstanceOf(ListInputDefinition::class, $innerListInputDefinition);
    $this->assertSame('integer', $innerListInputDefinition->getItemDefinition()->getDataType());

    // The multiple_numbers output is declared as the list that a multiple
    // output converts to (#3583049).
    $multipleOutputDefinition = $tool->getOutputDefinition('multiple_numbers');
    $this->assertInstanceOf(ListOutputDefinition::class, $multipleOutputDefinition);
    $this->assertFalse($multipleOutputDefinition->isMultiple());
    $this->assertSame('integer', $multipleOutputDefinition->getItemDefinition()->getDataType());

    $listOutputDefinition = $tool->getOutputDefinition('number_list');
    $this->assertInstanceOf(ListOutputDefinition::class, $listOutputDefinition);
    $this->assertFalse($listOutputDefinition->isMultiple());
    $this->assertSame('integer', $listOutputDefinition->getItemDefinition()->getDataType());

    $mapOutputDefinition = $tool->getOutputDefinition('number_map');
    $this->assertInstanceOf(MapOutputDefinition::class, $mapOutputDefinition);
    $this->assertSame('integer', $mapOutputDefinition->getPropertyDefinition('map_number')->getDataType());

    $multipleListOutputDefinition = $tool->getOutputDefinition('multiple_number_lists');
    $this->assertInstanceOf(ListOutputDefinition::class, $multipleListOutputDefinition);
    $innerListOutputDefinition = $multipleListOutputDefinition->getItemDefinition();
    $this->assertInstanceOf(ListOutputDefinition::class, $innerListOutputDefinition);
    $this->assertSame('integer', $innerListOutputDefinition->getItemDefinition()->getDataType());

    $inputSchema = $this->definitionSerializer->normalizeInputDefinitions($tool);
    $this->assertSame('array', $inputSchema['multiple_numbers']['type']);
    $this->assertSame('string', $inputSchema['multiple_numbers']['items']['type']);
    $this->assertSame('array', $inputSchema['number_list']['type']);
    $this->assertSame('string', $inputSchema['number_list']['items']['type']);
    $this->assertSame('object', $inputSchema['number_map']['type']);
    $this->assertSame('string', $inputSchema['number_map']['properties']['map_number']['type']);
    $this->assertSame('array', $inputSchema['multiple_number_lists']['type']);
    $this->assertSame('array', $inputSchema['multiple_number_lists']['items']['type']);
    $this->assertSame('string', $inputSchema['multiple_number_lists']['items']['items']['type']);

    $outputSchema = $this->definitionSerializer->normalizeOutputDefinitions($tool);
    $this->assertSame('array', $outputSchema['multiple_numbers']['type']);
    $this->assertSame('string', $outputSchema['multiple_numbers']['items']['type']);
    $this->assertSame('array', $outputSchema['number_list']['type']);
    $this->assertSame('string', $outputSchema['number_list']['items']['type']);
    $this->assertSame('object', $outputSchema['number_map']['type']);
    $this->assertSame('string', $outputSchema['number_map']['properties']['map_number']['type']);
    $this->assertSame('array', $outputSchema['multiple_number_lists']['type']);
    $this->assertSame('array', $outputSchema['multiple_number_lists']['items']['type']);
    $this->assertSame('string', $outputSchema['multiple_number_lists']['items']['items']['type']);

    $tool->setInputValue('multiple_numbers', ['1', '2']);
    $tool->setInputValue('number_list', ['3', '4']);
    $tool->setInputValue('number_map', ['map_number' => '5']);
    $tool->setInputValue('multiple_number_lists', [
      ['6', '7'],
      ['8', '9'],
    ]);
    $tool->execute();
    $this->assertTrue($tool->getResultStatus(), (string) $tool->getResultMessage());

    $rawValues = $tool->getResult()->getContextValues();
    $this->assertSame([1, 2], $rawValues['multiple_numbers']);
    $this->assertSame([3, 4], $rawValues['number_list']);
    $this->assertSame(['map_number' => 5], $rawValues['number_map']);
    $this->assertSame([[6, 7], [8, 9]], $rawValues['multiple_number_lists']);

    $formattedValues = $tool->getFormattedResult()->getContextValues();
    $this->assertSame(['1', '2'], $formattedValues['multiple_numbers']);
    $this->assertSame(['3', '4'], $formattedValues['number_list']);
    $this->assertSame(['map_number' => '5'], $formattedValues['number_map']);
    $this->assertSame([['6', '7'], ['8', '9']], $formattedValues['multiple_number_lists']);

    $inputScalarEvents = array_values(array_filter($inputEvents, fn (array $event): bool => $event['is_scalar_target']));
    $this->assertSame([
      'multiple_numbers',
      'multiple_numbers',
      'number_list',
      'number_list',
      'map_number',
      'multiple_number_lists',
      'multiple_number_lists',
      'multiple_number_lists',
      'multiple_number_lists',
    ], array_column($inputScalarEvents, 'name'));

    $outputScalarEvents = array_values(array_filter($outputEvents, fn (array $event): bool => $event['is_scalar_target']));
    $this->assertSame([
      'multiple_numbers',
      'multiple_numbers',
      'number_list',
      'number_list',
      'map_number',
      'multiple_number_lists',
      'multiple_number_lists',
      'multiple_number_lists',
      'multiple_number_lists',
    ], array_column($outputScalarEvents, 'name'));
  }

}
