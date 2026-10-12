<?php

declare(strict_types=1);

namespace Drupal\Tests\tool_ai_connector\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ai\Service\FunctionCalling\StructuredExecutableFunctionCallInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Handle\ToolHandleStore;
use Drupal\tool_ai_connector\Plugin\AiFunctionCall\ToolPluginBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests structured output from wrapped tool plugins.
 *
 * @group tool_ai_connector
 */
#[RunTestsInSeparateProcesses]
final class ToolPluginBaseStructuredOutputTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'serialization',
    'ai',
    'tool',
    'tool_test',
    'tool_ai_connector',
    'node',
    'field',
    'text',
  ];

  /**
   * The function call plugin manager.
   *
   * @var \Drupal\ai\Service\FunctionCalling\FunctionCallPluginManager
   */
  protected $functionCallManager;

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

    $this->functionCallManager = $this->container->get('plugin.manager.ai.function_calls');
    $this->definitionSerializer = $this->container->get('tool.definition_serializer');
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Tests the model receives failure messages as plain text.
   *
   * The invalid-input wrap escapes the curated message into a placeholder,
   * so without the plain-text conversion the quotes would reach the model
   * as &quot; entities (#3583042).
   */
  public function testFailureMessageIsPlainText(): void {
    $function_call = $this->functionCallManager->createInstance('tool:quoted_failure_tool');
    $function_call->execute();

    $output = $function_call->getStructuredOutput();
    $this->assertFalse($output['success']);
    $this->assertStringContainsString('The "subject" value is not usable.', $output['message']);
    $this->assertStringNotContainsString('&quot;', $output['message']);
  }

  /**
   * Tests structured output has a stable shape before execution.
   */
  public function testStructuredOutputBeforeExecutionUsesStableShape(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');

    $this->assertInstanceOf(StructuredExecutableFunctionCallInterface::class, $function_call);
    $this->assertSame([
      'success' => FALSE,
      'message' => 'Tool has not executed yet.',
      'outputs' => [],
    ], $function_call->getStructuredOutput());
    $this->assertStringContainsString('Tool has not executed yet.', $function_call->getReadableOutput());
  }

  /**
   * Tests a readable output stored via setOutput() is honored.
   *
   * Regression test for #3582948: a serialized/restored function call has
   * its readable output re-applied via FunctionCallBase::setOutput(), and
   * reading it back must return that string — without recomputing from the
   * fresh, un-executed tool instance (which historically threw
   * BadMethodCallException) and without losing the stored value.
   */
  public function testSetOutputStringIsHonoredByReadableOutput(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');

    // Restore a previously produced readable output before any execution.
    $function_call->setOutput('Restored readable output.');
    $this->assertSame('Restored readable output.', $function_call->getReadableOutput());

    // The structured shape is unaffected by the string restore.
    $this->assertSame('Tool has not executed yet.', $function_call->getStructuredOutput()['message']);

    // A fresh execution clears the restored string and produces live output.
    $function_call->setContextValue('number', 42);
    $function_call->execute();
    $this->assertStringContainsString('Executed with number: 42', $function_call->getReadableOutput());
  }

  /**
   * Tests successful tool output is exposed as structured output.
   */
  public function testStructuredOutputIncludesFormattedToolOutputs(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');
    $function_call->setContextValue('number', 42);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success']);
    $this->assertSame('Executed with number: 42', $structured_output['message']);
    $this->assertSame(['doubled' => 84], $structured_output['outputs']);
    $this->assertArrayNotHasKey('input_schema', $structured_output);

    $readable_output = $function_call->getReadableOutput();
    $this->assertStringContainsString('Tool Plugin executed successfully: Executed with number: 42', $readable_output);
    $this->assertStringContainsString('Output for doubled: 84', $readable_output);
  }

  /**
   * Tests validation failures keep violation detail and the input schema.
   *
   * Invalid input must not be reported as an access denial: the model needs
   * the violation text to correct its arguments, and "access denied" would
   * wrongly tell it to give up (#3582961).
   */
  public function testStructuredOutputIncludesInputSchemaForExecutionErrors(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('Tool plugin execution failed:', $structured_output['message']);
    $this->assertStringContainsString('number', $structured_output['message']);
    $this->assertSame([], $structured_output['outputs']);
    $this->assertSame('object', $structured_output['input_schema']['type']);
    $this->assertSame('integer', $structured_output['input_schema']['properties']['number']['type']);
    $this->assertSame(['number'], $structured_output['input_schema']['required']);

    $this->assertStringContainsString('The tool input schema is:', $function_call->getReadableOutput());
  }

  /**
   * Tests an explicitly NULL required input is a named validation failure.
   */
  public function testExplicitNullInputIsReportedAsValidationFailure(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');
    $function_call->setContextValue('number', NULL);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('number', $structured_output['message']);
    $this->assertSame('object', $structured_output['input_schema']['type']);
  }

  /**
   * Tests a genuine access denial is reported as such, without the schema.
   *
   * The reason from AccessResult::forbidden() is surfaced so the LLM knows
   * why access was denied (#3582989). The schema is omitted (#3582993): a
   * retry with different arguments cannot fix an access denial, so
   * attaching it would only invite a wasted retry.
   */
  public function testStructuredOutputOmitsInputSchemaForAccessDenials(): void {
    $function_call = $this->functionCallManager->createInstance('tool:access_denied_tool');
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertSame('Tool plugin access denied: Denied by design.', $structured_output['message']);
    $this->assertSame([], $structured_output['outputs']);
    $this->assertArrayNotHasKey('input_schema', $structured_output);
  }

  /**
   * Tests that an un-storable input value fails with the input named.
   *
   * Malformed JSON for a map input is left untouched by coercion and cannot
   * be stored in the Map typed data; the resulting InputException (#3582981)
   * must surface as a structured failure naming the input, with the input
   * schema echoed, so the LLM can self-correct — not as a generic exception.
   */
  public function testMapValueThatCannotBeStoredFailsWithInputNamed(): void {
    $function_call = $this->functionCallManager->createInstance('tool:coercion_test_tool');
    $function_call->setContextValue('tags', ['a']);
    $function_call->setContextValue('enabled', TRUE);
    $function_call->setContextValue('numbers', [1]);
    $function_call->setContextValue('settings', '{"verbose": tru');
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('The settings input value is invalid', $structured_output['message']);
    $this->assertSame('object', $structured_output['input_schema']['type']);
    $this->assertArrayHasKey('settings', $structured_output['input_schema']['properties']);
  }

  /**
   * Tests a runtime exception from the wrapped tool omits the schema.
   *
   * ThrowingTool's doExecute() throws a \RuntimeException. ToolBase::execute()
   * catches it and returns a failure result without rethrowing, so this
   * reaches getStructuredOutput() through the normal result path, not
   * through ToolPluginBase::execute()'s own catch block. The category on
   * that result is Runtime, so the schema is omitted (#3582993): the
   * failure has nothing to do with the input the LLM sent.
   */
  public function testStructuredOutputOmitsInputSchemaForRuntimeExceptions(): void {
    $function_call = $this->functionCallManager->createInstance('tool:throwing_tool');
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('Tool execution failed: RuntimeException', $structured_output['message']);
    $this->assertArrayNotHasKey('input_schema', $structured_output);
  }

  /**
   * Tests a malformed entity handle keeps the input schema.
   *
   * Setting the input dispatches the input transform event, which throws
   * InvalidHandleException from EntityHandleTransformer::resolveInput() for
   * a handle-prefixed string with an invalid UUID suffix. This reaches
   * ToolPluginBase::execute()'s own catch block (not the wrapped tool's),
   * so it must be classified as Input, not fall through to the generic
   * \Exception catch as Runtime. All three handle exceptions (this one and
   * the two below) share that classification (#3582993).
   *
   * @see \Drupal\tool\Handle\HandleExceptionInterface
   */
  public function testStructuredOutputIncludesInputSchemaForMalformedHandle(): void {
    $function_call = $this->functionCallManager->createInstance('tool:complex_entity_event_tool');
    $function_call->setContextValue('payload', [
      'title' => 'Launch',
      'profile' => [
        'alias' => 'Ada',
        'featured_entity' => 'handle:not-a-uuid',
      ],
      'items' => [],
    ]);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('is not a valid handle', $structured_output['message']);
    $this->assertSame('object', $structured_output['input_schema']['type']);
  }

  /**
   * Tests a missing/expired entity handle keeps the input schema.
   *
   * A well-formed handle that was never stored (or has expired) makes
   * ToolHandleStore::resolve() throw HandleNotFoundException.
   *
   * @see self::testStructuredOutputIncludesInputSchemaForMalformedHandle
   */
  public function testStructuredOutputIncludesInputSchemaForMissingHandle(): void {
    $missing_handle = 'handle:' . $this->container->get('uuid')->generate();
    $function_call = $this->functionCallManager->createInstance('tool:complex_entity_event_tool');
    $function_call->setContextValue('payload', [
      'title' => 'Launch',
      'profile' => [
        'alias' => 'Ada',
        'featured_entity' => $missing_handle,
      ],
      'items' => [],
    ]);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('does not exist or has expired', $structured_output['message']);
    $this->assertSame('object', $structured_output['input_schema']['type']);
  }

  /**
   * Tests a handle resolving to a non-entity value keeps the input schema.
   *
   * A valid, stored handle that points to a non-content-entity value makes
   * EntityHandleTransformer::resolveInput() throw
   * UnexpectedHandleValueException.
   *
   * @see self::testStructuredOutputIncludesInputSchemaForMalformedHandle
   */
  public function testStructuredOutputIncludesInputSchemaForNonEntityHandle(): void {
    $non_entity_handle = $this->handleStore->store('just a string');
    $function_call = $this->functionCallManager->createInstance('tool:complex_entity_event_tool');
    $function_call->setContextValue('payload', [
      'title' => 'Launch',
      'profile' => [
        'alias' => 'Ada',
        'featured_entity' => $non_entity_handle,
      ],
      'items' => [],
    ]);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertFalse($structured_output['success']);
    $this->assertStringContainsString('does not reference a content entity', $structured_output['message']);
    $this->assertSame('object', $structured_output['input_schema']['type']);
  }

  /**
   * Tests replayed structured output also drives readable output.
   */
  public function testStructuredOutputReplayProvidesReadableOutput(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');
    $function_call->setStructuredOutput([
      'success' => TRUE,
      'message' => "Replayed result.\nA replay hint.",
      'outputs' => [
        'doubled' => 12,
      ],
    ]);

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success']);
    $this->assertSame("Replayed result.\nA replay hint.", $structured_output['message']);
    $this->assertSame(['doubled' => 12], $structured_output['outputs']);

    $readable_output = $function_call->getReadableOutput();
    $this->assertStringContainsString('Tool Plugin executed successfully: Replayed result.', $readable_output);
    $this->assertStringContainsString('Output for doubled: 12', $readable_output);
    $this->assertStringContainsString('A replay hint.', $readable_output);
  }

  /**
   * Tests a structured output override set after execution always wins.
   *
   * Regression test for the replay contract: FunctionCallBase returns a
   * setStructuredOutput() value unconditionally, and the AI module's own
   * kernel tests exercise execute() followed by setStructuredOutput(). The
   * override must win over the live tool result until the next execute()
   * clears it.
   */
  public function testStructuredOutputOverrideAfterExecution(): void {
    $function_call = $this->functionCallManager->createInstance('tool:numeric_input_tool');
    $function_call->setContextValue('number', 42);
    $function_call->execute();

    // Sanity check the live result first.
    $this->assertSame(['doubled' => 84], $function_call->getStructuredOutput()['outputs']);

    // An override set after execution takes precedence in both getters.
    $function_call->setStructuredOutput([
      'success' => TRUE,
      'message' => 'Replayed override.',
      'outputs' => [
        'doubled' => 12,
      ],
    ]);
    $structured_output = $function_call->getStructuredOutput();
    $this->assertSame('Replayed override.', $structured_output['message']);
    $this->assertSame(['doubled' => 12], $structured_output['outputs']);
    $this->assertStringContainsString('Replayed override.', $function_call->getReadableOutput());

    // A fresh execution clears the override and produces live output again.
    $function_call->execute();
    $structured_output = $function_call->getStructuredOutput();
    $this->assertSame('Executed with number: 42', $structured_output['message']);
    $this->assertSame(['doubled' => 84], $structured_output['outputs']);
  }

  /**
   * Tests colon-containing nested property names round-trip through escaping.
   *
   * The AI module requires alphanumeric property names, so nested names are
   * advertised escaped ('field:tags' as 'field__colon__tags'). The AI module
   * only unescapes top-level argument names; nested keys arrive escaped and
   * must be unescaped before values reach the Tool plugin, or they match no
   * property definition and are silently dropped.
   */
  public function testColonPropertyNamesRoundTripThroughEscaping(): void {
    $function_call = $this->functionCallManager->createInstance('tool:colon_map_tool');

    // The advertised schema carries the escaped nested name.
    $rendered = $function_call->normalize()->renderFunctionArray();
    $payload_properties = $rendered['parameters']['properties']['payload']['properties'];
    $this->assertArrayHasKey('field__colon__tags', $payload_properties);
    $this->assertArrayNotHasKey('field:tags', $payload_properties);

    // The LLM sends the value back under the escaped nested key; execution
    // unescapes it so the tool receives the real property name.
    $function_call->setContextValue('payload', ['field__colon__tags' => 'alpha']);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success'], (string) $structured_output['message']);
    $this->assertSame(['field:tags' => 'alpha'], $structured_output['outputs']['payload']);
  }

  /**
   * Tests normalized AI input schemas use handles for nested entity inputs.
   */
  public function testNormalizeUsesHandleSchemasForNestedEntityInputs(): void {
    $function_call = $this->functionCallManager->createInstance('tool:complex_entity_event_tool');

    $rendered = $function_call->normalize()->renderFunctionArray();
    $payload_schema = $rendered['parameters']['properties']['payload'];

    $featured_entity_schema = $payload_schema['properties']['profile']['properties']['featured_entity'];
    $this->assertSame('string', $featured_entity_schema['type']);
    $this->assertStringContainsString('pass a handle', $featured_entity_schema['description']);

    $related_entity_schema = $payload_schema['properties']['items']['items']['properties']['metadata']['properties']['related_entity'];
    $this->assertSame('string', $related_entity_schema['type']);
    $this->assertStringContainsString('pass a handle', $related_entity_schema['description']);
  }

  /**
   * Tests nested entity handles round-trip through tool input and output.
   */
  public function testNestedEntityHandlesRoundTripThroughStructuredOutput(): void {
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

    $function_call = $this->functionCallManager->createInstance('tool:complex_entity_event_tool');
    $this->assertInstanceOf(ToolPluginBase::class, $function_call);
    $output_schema = $this->definitionSerializer->normalizeOutputDefinitions($function_call->getPluginInstance());
    $related_output_schema = $output_schema['report']['properties']['items']['items']['properties']['metadata']['properties']['related_entity'];
    $this->assertSame('string', $related_output_schema['type']);
    $this->assertStringContainsString('returns a handle', $related_output_schema['description']);

    $function_call->setContextValue('payload', [
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
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success'], $structured_output['message']);
    $this->assertStringContainsString('Complex entity report generated.', $structured_output['message']);

    $report = $structured_output['outputs']['report'];
    $this->assertSame('Launch / Ada', $report['summary']);
    $this->assertSame($featured_handle, $report['profile']['featured_entity']);
    $this->assertSame($related_handle, $report['items'][0]['metadata']['related_entity']);

    $resolved_featured = $this->handleStore->resolve($featured_handle);
    $resolved_related = $this->handleStore->resolve($related_handle);
    $this->assertInstanceOf(Node::class, $resolved_featured);
    $this->assertInstanceOf(Node::class, $resolved_related);
    $this->assertSame('Featured entity returned', $resolved_featured->getTitle());
    $this->assertSame('Related entity returned 1', $resolved_related->getTitle());

    // Hints are now embedded in the message rather than a separate key.
    $this->assertStringContainsString($featured_handle, $structured_output['message']);
    $this->assertStringContainsString($related_handle, $structured_output['message']);

    $readable_output = $function_call->getReadableOutput();
    $this->assertStringContainsString($featured_handle, $readable_output);
    $this->assertStringContainsString($related_handle, $readable_output);
  }

  /**
   * Tests batch hint for a tool that outputs a list of entities.
   *
   * When an output definition is a list of entities, declared as the list a
   * multiple entity output converts to (#3583049), the subscriber should
   * produce a single summarized hint containing all handles and their
   * metadata, rather than one hint per entity.
   */
  public function testEntityListOutputProducesBatchHint(): void {
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'First article',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => 'Second article',
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => 'article',
      'title' => 'Third article',
    ]);
    $node3->save();

    $function_call = $this->functionCallManager->createInstance('tool:entity_list_output_tool');
    $function_call->setContextValue('count', 3);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success'], $structured_output['message']);
    $this->assertStringContainsString('Loaded 3 entities.', $structured_output['message']);

    // The outputs should contain an array of handle strings.
    $entities_output = $structured_output['outputs']['entities'];
    $this->assertIsArray($entities_output);
    $this->assertCount(3, $entities_output);
    foreach ($entities_output as $handle) {
      $this->assertIsString($handle);
      $this->assertStringStartsWith('handle:', $handle);
    }

    // The message should contain ONE batch hint with all handles.
    $message = $structured_output['message'];
    $this->assertStringContainsString('Output `entities` contains 3 entity handle(s):', $message);

    // Each handle and its metadata should appear in the hint.
    foreach ($entities_output as $handle) {
      $this->assertStringContainsString($handle, $message);
    }

    // Per-entity metadata should be present.
    $this->assertStringContainsString('bundle: article', $message);
    $this->assertStringContainsString('id: ' . $node1->id(), $message);
    $this->assertStringContainsString('id: ' . $node2->id(), $message);
    $this->assertStringContainsString('id: ' . $node3->id(), $message);

    // Labels should be included (access is granted in test).
    $this->assertStringContainsString('label: First article', $message);
    $this->assertStringContainsString('label: Second article', $message);
    $this->assertStringContainsString('label: Third article', $message);

    // The hint instruction should appear only ONCE (batch, not per-entity).
    $this->assertSame(
      1,
      substr_count($message, 'Pass these handles to subsequent tool calls that accept entity inputs.'),
      'The batch hint instruction should appear exactly once.',
    );

    // Readable output should also contain the batch hint.
    $readable_output = $function_call->getReadableOutput();
    $this->assertStringContainsString('Output `entities` contains 3 entity handle(s):', $readable_output);
  }

  /**
   * Tests batch input resolve for a list of entity handles.
   *
   * When an input definition is a ListInputDefinition with entity items,
   * the subscriber resolves all handles in a single pass. This verifies
   * that the tool receives resolved entities, not handle strings.
   */
  public function testEntityListInputBatchResolve(): void {
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'Input Node 1',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => 'Input Node 2',
    ]);
    $node2->save();

    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);

    $function_call = $this->functionCallManager->createInstance('tool:entity_list_input_output_tool');
    $function_call->setContextValue('entities', [$handle1, $handle2]);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success'], $structured_output['message']);
    $this->assertStringContainsString('Processed 2 entities.', $structured_output['message']);

    // The output should contain handle strings (transformed back).
    $processed = $structured_output['outputs']['processed'];
    $this->assertIsArray($processed);
    $this->assertCount(2, $processed);
    foreach ($processed as $handle) {
      $this->assertIsString($handle);
      $this->assertStringStartsWith('handle:', $handle);
    }

    // Verify the tool actually received resolved entities (titles changed).
    $resolved1 = $this->handleStore->resolve($processed[0]);
    $resolved2 = $this->handleStore->resolve($processed[1]);
    $this->assertSame('Processed: Input Node 1', $resolved1->getTitle());
    $this->assertSame('Processed: Input Node 2', $resolved2->getTitle());
  }

  /**
   * Tests the list output definition path produces a batch hint.
   *
   * This exercises the isEntityListDefinition() path that checks for a list
   * output definition with entity item definitions, here with an item that
   * describes itself; testEntityListOutputProducesBatchHint() covers a list
   * whose item is bare, as the item of a converted multiple output is.
   */
  public function testListOutputDefinitionProducesBatchHint(): void {
    $node1 = Node::create([
      'type' => 'article',
      'title' => 'List Node 1',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => 'List Node 2',
    ]);
    $node2->save();

    $handle1 = $this->handleStore->store($node1);
    $handle2 = $this->handleStore->store($node2);

    $function_call = $this->functionCallManager->createInstance('tool:entity_list_input_output_tool');
    $function_call->setContextValue('entities', [$handle1, $handle2]);
    $function_call->execute();

    $structured_output = $function_call->getStructuredOutput();
    $this->assertTrue($structured_output['success'], $structured_output['message']);

    // The message should contain a batch hint for the 'processed' output.
    $message = $structured_output['message'];
    $this->assertStringContainsString('Output `processed` contains 2 entity handle(s):', $message);

    // Per-entity metadata in markdown-kv format.
    $this->assertStringContainsString('type: node', $message);
    $this->assertStringContainsString('bundle: article', $message);
    $this->assertStringContainsString('label: Processed: List Node 1', $message);
    $this->assertStringContainsString('label: Processed: List Node 2', $message);

    // Only ONE batch instruction, not per-entity.
    $this->assertSame(
      1,
      substr_count($message, 'Pass these handles to subsequent tool calls that accept entity inputs.'),
    );
  }

}
