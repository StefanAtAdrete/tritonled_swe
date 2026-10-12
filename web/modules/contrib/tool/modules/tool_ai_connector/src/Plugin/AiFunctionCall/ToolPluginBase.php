<?php

namespace Drupal\tool_ai_connector\Plugin\AiFunctionCall;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\OperationType\Chat\Tools\ToolsFunctionInput;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\StructuredExecutableFunctionCallInterface;
use Drupal\tool\Exception\InputException;
use Drupal\tool\FailureCategory;
use Drupal\tool\Handle\HandleExceptionInterface;
use Drupal\tool\Normalizer\ToolDefinitionSerializer;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool_ai_connector\Context\ToolContextDefinition;
use Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface;
use Drupal\tool_ai_connector\Plugin\AiFunctionCall\Derivative\ToolPluginDeriver;
use Drupal\tool_ai_connector\SchemaToolsPropertyConverter;
use Drupal\tool_ai_connector\ToolAiConnectorInvoker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Bridges Tool module plugins into the AI module's function calling system.
 *
 * Each Tool plugin is exposed as an AI function call via a deriver
 * (ToolPluginDeriver). This class wraps the underlying Tool plugin, translating
 * between the AI module's context/execute/output contract and the Tool module's
 * typed input/output/event pipeline.
 *
 * Lifecycle:
 * 1. The AI module sets context values via setContextValue() (LLM arguments).
 * 2. execute() passes those values to the wrapped Tool plugin as typed inputs,
 *    checks access, and triggers Tool::execute().
 * 3. getStructuredOutput() retrieves the formatted result (which has already
 *    passed through the invoker-aware output transform pipeline) and returns
 *    a normalized array the AI module can serialize as a tool response.
 * 4. getReadableOutput() produces a human-readable string from the structured
 *    output for display in explorer UIs or text-based LLM responses.
 *
 * The invoker identity (INVOKER_ID) is set on the Tool plugin so that event
 * subscribers (e.g., entity handle transforms) know to activate for AI calls.
 */
#[FunctionCall(
  id: 'tool',
  function_name: 'tool',
  name: new TranslatableMarkup('Tool Plugin Wrapper'),
  description: '',
  deriver: ToolPluginDeriver::class
)]
final class ToolPluginBase extends FunctionCallBase implements StructuredExecutableFunctionCallInterface {

  /**
   * Input values received from the AI module (LLM arguments).
   *
   * Keyed by input name. These are passed to the wrapped Tool plugin's
   * setInputValue() during execute().
   *
   * @var array
   */
  protected array $values = [];

  /**
   * Context definition adapters keyed by input name.
   *
   * @var array<string, \Drupal\Core\Plugin\Context\ContextDefinitionInterface&\Drupal\tool_ai_connector\Context\ToolContextDefinitionInterface>
   */
  protected array $adaptedContextDefinitions = [];

  /**
   * An externally set structured output override (e.g. a replay).
   *
   * When set via setStructuredOutput(), this always wins over computing the
   * output from the wrapped Tool plugin, matching the unconditional getter
   * contract of FunctionCallBase. Cleared by execute(), so a fresh execution
   * produces fresh output.
   *
   * execute() also uses this field directly, through recordFailure(), for a
   * failure it detects before the wrapped Tool plugin can produce its own
   * result (invalid input, access denial, or an exception from this
   * method's own code).
   *
   * @var array|null
   */
  protected ?array $structuredOutputOverride = NULL;

  /**
   * The lazily-instantiated wrapped Tool plugin.
   *
   * Created on first access via getPluginInstance(). The derivative ID
   * determines which Tool plugin to instantiate.
   *
   * @var \Drupal\tool\Tool\ToolInterface|null
   */
  protected ?ToolInterface $toolPluginInstance;

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected ToolManager $toolManager;

  /**
   * Serializes Tool input/output definitions into JSON Schema format.
   *
   * @var \Drupal\tool\Normalizer\ToolDefinitionSerializer
   */
  protected ToolDefinitionSerializer $toolDefinitionSerializer;

  /**
   * Converts JSON Schema properties into the AI module's ToolsPropertyInput.
   *
   * @var \Drupal\tool_ai_connector\SchemaToolsPropertyConverter
   */
  protected SchemaToolsPropertyConverter $schemaToolsPropertyConverter;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->toolManager = $container->get('plugin.manager.tool');
    $instance->toolDefinitionSerializer = $container->get('tool.definition_serializer');
    $instance->schemaToolsPropertyConverter = $container->get('tool_ai_connector.schema_tools_property_converter');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * Checks the declared permission first, then passes each context value to
   * the Tool plugin as a typed input (triggering input transform events for
   * handle resolution, etc.), checks access, and then executes. Any
   * exception is caught and recorded as a failure rather than propagating,
   * because the AI module expects a result, not an exception.
   *
   * A failure reached here, before the wrapped Tool plugin's own execute()
   * runs, gets a \Drupal\tool\FailureCategory assigned directly by this
   * method, since there is no ExecutableResult yet to read one from. A
   * failure inside the wrapped Tool plugin's own execute() already carries
   * a category on its result (\Drupal\tool\Tool\ToolBase::execute() sets
   * it); getStructuredOutput() reads that one instead of deciding again.
   */
  public function execute() {
    $this->structuredOutputOverride = NULL;
    $this->stringOutput = '';

    try {
      // Check the declared permission before touching any model-provided
      // value, so an account without it is denied before transforms resolve
      // handles or refiners run (#3583077). access() below still gates
      // execution. Reported the way an access() denial is, so the model
      // sees one denial shape.
      /** @var \Drupal\tool\Tool\ToolDefinition $tool_definition */
      $tool_definition = $this->getPluginInstance()->getPluginDefinition();
      $permission_access = ToolManager::checkPermission($tool_definition, $this->currentUser);
      if (!$permission_access->isAllowed()) {
        $this->recordAccessDenial($permission_access);
        return;
      }

      // Pass LLM-provided values to the Tool plugin as typed inputs.
      // Only values matching declared input definitions are forwarded.
      // Each setInputValue() dispatches a ToolInputTransformEvent, allowing
      // subscribers to resolve handles, validate formats, etc.
      foreach (array_keys($this->getPluginInstance()->getInputDefinitions()) as $name) {
        if (array_key_exists($name, $this->values)) {
          $this->getPluginInstance()->setInputValue($name, $this->unescapePropertyKeys($this->values[$name]));
          // Mirror the tool's input into the parent contexts so context
          // data is readable after execution (#3576586). Done here rather
          // than in setContextValue() so contexts hold transformed values
          // (upcasted entities, not handle strings). The context is built
          // at this boundary: the Tool API stores typed data and no longer
          // deals in core contexts (#3583018).
          $tool = $this->getPluginInstance();
          $context_definition = ToolContextDefinition::fromToolDefinition($tool->getInputDefinition($name));
          // Mirror the value as the tool resolves it, so an explicit NULL
          // reads as the definition default rather than a typed-data empty
          // shape. Only this input is read: resolving them all would let an
          // unrelated unresolvable input fail this one. A required input
          // that cannot resolve mirrors as a value-less context, as it did
          // before #3583018; validation reports it right below.
          try {
            $context_value = $tool->getInputValue($name);
          }
          catch (ContextException) {
            $context_value = NULL;
          }
          $this->setContext($name, new Context($context_definition, $context_value));
        }
      }
      // Validate explicitly before the access check. access() fails closed
      // with a generic reason on invalid input (#3582961), but the model
      // needs the violation detail, and "access denied" would wrongly tell
      // it to give up rather than correct its arguments.
      $violations = $this->getPluginInstance()->validateInputs();
      if ($violations->count() > 0) {
        $this->recordFailure(
          new TranslatableMarkup('Tool plugin execution failed: @violations', ['@violations' => (string) $violations]),
          FailureCategory::Input,
        );
        return;
      }

      $access = $this->getPluginInstance()->access(NULL, TRUE);
      if (!$access->isAllowed()) {
        $this->recordAccessDenial($access);
        return;
      }

      $this->getPluginInstance()->execute();
      // Materialize the formatted result now so output transforms (entity →
      // handle downcasting into the handle store) run at execute time, not
      // as a render-time side effect. Later tool calls in the same batch
      // resolve handles from this call regardless of when the runner
      // collects its output. getFormattedResult() is memoized, so output
      // getters reuse this result.
      $this->getPluginInstance()->getFormattedResult();
    }
    catch (InputException $e) {
      // setInputValue() above throws this when a value cannot be stored at
      // all (e.g. a scalar passed for a map input), before validateInputs()
      // gets a chance to name it in violation text. Still an input problem,
      // so it keeps the input schema.
      $this->recordFailure(
        new TranslatableMarkup('Tool plugin execution failed: @message', ['@message' => $e->getMessage()]),
        FailureCategory::Input,
      );
    }
    catch (HandleExceptionInterface $e) {
      // setInputValue() above dispatches the input transform event, which
      // throws one of these for a malformed, missing, or wrong-type entity
      // handle argument.
      // @see \Drupal\tool\Handle\HandleExceptionInterface
      $this->recordFailure(
        new TranslatableMarkup('Tool plugin execution failed: @message', ['@message' => $e->getMessage()]),
        FailureCategory::Input,
      );
    }
    catch (\Exception $e) {
      // Anything else caught here is a bug in this method's own code (for
      // example the output-transform pipeline in getFormattedResult()),
      // not in the wrapped tool's business logic. Reshaping the arguments
      // cannot fix it.
      $this->recordFailure(
        new TranslatableMarkup('Tool plugin execution failed: @message', ['@message' => $e->getMessage()]),
        FailureCategory::Runtime,
      );
    }
  }

  /**
   * Records an access denial, from either access stage.
   *
   * The declared-permission pre-check and access() report through this one
   * method, so the model sees one denial shape whichever stage denied
   * (#3583077).
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The denying access result.
   */
  protected function recordAccessDenial(AccessResultInterface $access): void {
    $reason = $access instanceof AccessResultReasonInterface ? $access->getReason() : NULL;
    $message = $reason
      ? new TranslatableMarkup('Tool plugin access denied: @reason', ['@reason' => $reason])
      : new TranslatableMarkup('Tool plugin access denied.');
    $this->recordFailure($message, FailureCategory::Access);
  }

  /**
   * Records a failure reached before the wrapped Tool plugin produced one.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The failure message.
   * @param \Drupal\tool\FailureCategory $category
   *   The failure category. Controls whether the input schema is attached.
   */
  protected function recordFailure(TranslatableMarkup $message, FailureCategory $category): void {
    $output = [
      'success' => FALSE,
      // The model reads the message as text; escaped placeholders would
      // reach it as entities (#3583042).
      'message' => PlainTextOutput::renderFromHtml((string) $message),
      'outputs' => [],
    ];
    if ($category->isCorrectable()) {
      $output['input_schema'] = $this->getInputSchema();
    }
    $this->structuredOutputOverride = $output;

    // The tool never executed, so record a failure result on it too, so
    // external callers that read the result directly (for example
    // ai_agents' image collection loop, which calls getResult() without
    // checking hasResult()) receive the failure instead of a
    // BadMethodCallException masking the error feedback above.
    if (!$this->getPluginInstance()->hasResult()) {
      $this->getPluginInstance()->setFailureResult($message, $category);
    }
  }

  /**
   * {@inheritdoc}
   *
   * A readable output stored via setOutput() (e.g. restored from a
   * serialized function call) takes precedence, honoring the
   * FunctionCallBase contract; execute() clears it so a fresh execution
   * produces fresh output. Otherwise this delegates to getStructuredOutput()
   * so both methods share the same source of truth (including hints already
   * embedded in the message).
   */
  public function getReadableOutput(): string {
    if ($this->stringOutput !== '') {
      return $this->stringOutput;
    }

    $structured_output = $this->getStructuredOutput();
    $message = (string) ($structured_output['message'] ?? '');

    if (!empty($structured_output['success'])) {
      $output = (string) $this->t('Tool Plugin executed successfully: @result', [
        '@result' => $message,
      ]);
    }
    else {
      $output = (string) $this->t('Tool Plugin executed unsuccessfully: @result', [
        '@result' => $message,
      ]);
    }

    foreach ($structured_output['outputs'] ?? [] as $key => $value) {
      $output .= "\nOutput for " . $key . ": " . $this->stringifyOutputValue($value);
    }

    if (isset($structured_output['input_schema'])) {
      $output .= "\nThe tool input schema is: " . json_encode($structured_output['input_schema']);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   *
   * State handling:
   * - If setStructuredOutput() was called (replay/override), that always
   *   takes precedence over computing from the Tool plugin, until the next
   *   execute() clears it. So does a failure recorded by execute()'s own
   *   recordFailure(), which is stored the same way; see execute() for why.
   * - If the tool hasn't executed yet, returns the default
   *   "not executed" shape.
   *
   * Hints (e.g., entity handle metadata) are appended directly to
   * 'message' rather than a separate key, because the AI module renders
   * 'message' as the tool response content seen by the LLM.
   */
  public function getStructuredOutput(): array {
    // External override (e.g., replayed from a previous execution) or a
    // failure recorded by execute()'s own recordFailure().
    if ($this->structuredOutputOverride !== NULL) {
      return $this->structuredOutputOverride;
    }

    // Tool hasn't been executed yet.
    if (!$this->hasToolResult()) {
      return $this->getDefaultStructuredOutput();
    }

    // Normal path: get the formatted result from the Tool plugin.
    // getFormattedResult() dispatches ToolOutputTransformEvent per output,
    // which transforms entities to handles and collects hints.
    $formatted_result = $this->getPluginInstance()->getFormattedResult();
    // The model reads the message as text; escaped placeholders would
    // reach it as entities (#3583042).
    $message = PlainTextOutput::renderFromHtml((string) $formatted_result->getMessage());

    // Append hints (handle metadata, usage instructions) directly to the
    // message so they appear in the tool response the LLM sees.
    if ($formatted_result->getHints() !== []) {
      $message .= "\n" . implode("\n", $formatted_result->getHints());
    }

    $output = [
      'success' => $formatted_result->isSuccess(),
      'message' => $message,
      'outputs' => $formatted_result->getContextValues(),
    ];

    // On failure, include the input schema only when the failure category
    // says a retry with different arguments could plausibly help. An
    // access denial or a runtime exception cannot be fixed by reshaping
    // the input, so attaching the schema there would only waste the LLM's
    // retries (#3582993).
    if (!$formatted_result->isSuccess() && $formatted_result->getFailureCategory()?->isCorrectable()) {
      $output['input_schema'] = $this->getInputSchema();
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function setStructuredOutput(array $output): void {
    $this->structuredOutputOverride = $output + $this->getDefaultStructuredOutput();
  }

  /**
   * {@inheritdoc}
   *
   * Converts the Tool plugin's input definitions (via JSON Schema) into
   * the AI module's ToolsFunctionInput property objects.
   */
  public function normalize(): ToolsFunctionInput {
    $function_attributes = $this->pluginDefinition;
    $function = new ToolsFunctionInput($function_attributes['function_name'], []);
    $function->setDescription($function_attributes['description']);

    $input_schema = $this->getInputSchema();
    $required = $input_schema['required'] ?? [];
    foreach ($input_schema['properties'] ?? [] as $name => $property_schema) {
      $function->setProperty($this->schemaToolsPropertyConverter->convert($name, $property_schema, in_array($name, $required, TRUE)));
    }

    return $function;
  }

  /**
   * Builds the JSON Schema for this tool's inputs, including overrides.
   *
   * The schema is built from the Tool plugin's input definitions, serialized
   * through the definition normalizer (which dispatches
   * ToolInputDefinitionNormalizeEvent to allow entity definitions to be
   * rewritten as handle string descriptions).
   *
   * Context definition overrides (set by the AI module for agent-level
   * constraints) are applied on top, potentially adding/removing required
   * fields or replacing definitions entirely.
   *
   * @return array
   *   A JSON Schema object with 'type', 'properties', and 'required'.
   */
  public function getInputSchema(): array {
    $tool = $this->getPluginInstance();
    $schema = $this->toolDefinitionSerializer->normalizeInputSchema($tool);

    // Apply per-instance overrides (e.g., agent pinning a value or changing
    // a field's required status).
    if ($this->contextDefinitionOverrides !== []) {
      $this->applyContextDefinitionOverridesToInputSchema($schema);
    }

    if (isset($schema['required'])) {
      $schema['required'] = array_values(array_unique($schema['required']));
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   *
   * Merges the AI module's context definition overrides with the Tool plugin's
   * input definitions. The + operator gives precedence to the left operand,
   * so overrides always win over the tool's declared inputs.
   */
  public function getContextDefinitions() {
    // phpcs:ignore
    // @see https://git.drupalcode.org/project/ai/-/work_items/3584421
    // The ai module's contract is typed on core context definitions, and
    // the deriver seeds the attribute-level definitions with the tool's
    // own, which are typed data since #3583017. Adapt every tool
    // definition at this boundary - the seeded ones included, or the
    // adapters would be shadowed by the merge - and let the adapters carry
    // the original definitions for consumers needing the richer shape.
    $definitions = parent::getContextDefinitions() + $this->getPluginInstance()->getInputDefinitions();
    foreach ($definitions as $name => $definition) {
      if ($definition instanceof InputDefinitionInterface) {
        // Reuse the adapter while it wraps the same definition object, so
        // repeated reads stay identity-stable; input refinement swaps the
        // definition and gets a fresh adapter.
        $adapted = $this->adaptedContextDefinitions[$name] ?? NULL;
        if ($adapted === NULL || $adapted->getToolDefinition() !== $definition) {
          $adapted = ToolContextDefinition::fromToolDefinition($definition);
          $this->adaptedContextDefinitions[$name] = $adapted;
        }
        $definitions[$name] = $adapted;
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * Derives from getContextDefinitions() so both methods share the same
   * source of truth (overrides + annotation defs + plugin input defs).
   */
  public function getContextDefinition($name) {
    // phpcs:ignore
    // @see https://git.drupalcode.org/project/ai/-/work_items/3584421
    $definitions = $this->getContextDefinitions();
    return $definitions[$name] ?? parent::getContextDefinition($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function setContextValue($name, $value) {
    $this->values[$name] = $value;
    return $this;
  }

  /**
   * Gets or lazily creates the wrapped Tool plugin instance.
   *
   * The derivative ID maps 1:1 to a Tool plugin ID. The invoker is set so
   * that event subscribers (handle transforms, definition normalizers) know
   * this execution originates from the AI connector.
   */
  public function getPluginInstance(): ToolInterface {
    if (!isset($this->toolPluginInstance)) {
      $this->toolPluginInstance = $this->toolManager->createInstance(
        $this->getDerivativeId(),
        [],
        ToolAiConnectorInvoker::create(),
      );
    }
    return $this->toolPluginInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function validateContexts() {
    return new ConstraintViolationList();
  }

  /**
   * Applies per-instance context definition overrides to the input schema.
   *
   * Overrides can add new properties, change required status, or replace
   * existing definitions. This is used by the AI module when an agent or
   * automation needs to pin certain inputs or alter the tool's interface.
   *
   * @param array $schema
   *   The schema to alter (passed by reference).
   */
  protected function applyContextDefinitionOverridesToInputSchema(array &$schema): void {
    $schema['properties'] ??= [];
    $schema['required'] ??= [];

    $tool = $this->getPluginInstance();
    foreach ($this->contextDefinitionOverrides as $name => $definition) {
      $target = $definition;
      if ($definition instanceof ToolContextDefinitionInterface) {
        // An override cloned from one of this class's adapters - the ai
        // module does this to pin values or change the required flag -
        // carries the original tool definition. toToolDefinition() rebuilds
        // it with the adapter's complete mutable state applied, so map
        // structure and entity handle rewrites survive and runtime and
        // schema read the same interpretation.
        $target = $definition->toToolDefinition();
      }
      $schema['properties'][$name] = $this->toolDefinitionSerializer->normalizeInputDefinition(
        $tool,
        $name,
        $target,
      );
      if ($definition instanceof ContextDefinitionInterface && $definition->isRequired()) {
        $schema['required'][] = $name;
      }
      else {
        $schema['required'] = array_values(array_diff($schema['required'], [$name]));
      }
    }
  }

  /**
   * Checks whether the wrapped Tool plugin has produced an execution result.
   *
   * @return bool
   *   TRUE if a tool result is available, FALSE otherwise.
   */
  protected function hasToolResult(): bool {
    return $this->getPluginInstance()->hasResult();
  }

  /**
   * Recursively unescapes reserved characters in a value's array keys.
   *
   * Property names are escaped (':' as '__colon__') when advertised to the
   * AI provider, because the AI module requires alphanumeric property names.
   * The AI module unescapes top-level argument names when populating values,
   * but nested keys inside array values arrive still escaped; they are
   * unescaped here so they match the tool's property definitions.
   *
   * @param mixed $value
   *   The value to unescape.
   *
   * @return mixed
   *   The value with unescaped array keys.
   */
  protected function unescapePropertyKeys(mixed $value): mixed {
    if (!is_array($value)) {
      return $value;
    }
    $unescaped = [];
    foreach ($value as $key => $item) {
      if (is_string($key)) {
        $key = str_replace('__colon__', ':', $key);
      }
      $unescaped[$key] = $this->unescapePropertyKeys($item);
    }
    return $unescaped;
  }

  /**
   * Returns the default structured output shape for "not yet executed" state.
   *
   * @return array
   *   The default structured output.
   */
  protected function getDefaultStructuredOutput(): array {
    return [
      'success' => FALSE,
      'message' => 'Tool has not executed yet.',
      'outputs' => [],
    ];
  }

  /**
   * Converts an output value to a string for human-readable rendering.
   *
   * @param mixed $value
   *   The value to format.
   *
   * @return string
   *   The formatted value (scalar cast, JSON for arrays/objects, 'NULL').
   */
  protected function stringifyOutputValue(mixed $value): string {
    if ($value === NULL) {
      return 'NULL';
    }
    // Render booleans explicitly — a plain string cast would print TRUE as
    // '1' and FALSE as '', which is ambiguous in LLM-facing output and
    // inconsistent with the Drush output formatting.
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
      return (string) $value;
    }
    if (is_object($value) && method_exists($value, '__toString')) {
      return (string) $value;
    }
    $encoded = json_encode($value);
    return $encoded === FALSE ? '' : $encoded;
  }

}
