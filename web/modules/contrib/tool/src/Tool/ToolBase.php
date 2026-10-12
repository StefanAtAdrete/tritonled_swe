<?php

declare(strict_types=1);

namespace Drupal\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Exception\InputException;
use Drupal\tool\FailureCategory;
use Drupal\tool\FormattedExecutableResult;
use Drupal\tool\TypedInputsTrait;
use Drupal\tool\TypedOutputsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for tool plugins.
 */
abstract class ToolBase extends PluginBase implements ToolInterface, ContainerFactoryPluginInterface {
  use PluginWithFormsTrait;
  use TypedInputsTrait {
    TypedInputsTrait::setInputValue as doSetInputValue;
  }
  use TypedOutputsTrait;

  /**
   * The result of the tool execution.
   *
   * @var \Drupal\tool\ExecutableResult|null
   */
  protected ?ExecutableResult $result;

  /**
   * The memoized formatted result.
   *
   * Computed once per execution by getFormattedResult() and reset by
   * execute(), so repeated getters do not re-run the output transform
   * event pipeline.
   *
   * @var \Drupal\tool\FormattedExecutableResult|null
   */
  protected ?FormattedExecutableResult $formattedResult = NULL;

  /**
   * Whether execute() has been called on this instance.
   *
   * Distinct from a result being available: setFailureResult() records a
   * result without executing.
   *
   * @var bool
   */
  protected bool $executed = FALSE;

  /**
   * The invoker, or NULL when called directly from PHP.
   */
  protected ?Invoker $invoker = NULL;

  /**
   * Constructs a ToolBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The tool logger channel. Subclasses redeclaring this property must use
   *   the exact type LoggerChannelInterface; any other type is a fatal
   *   property type mismatch.
   */
  final public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected AccountInterface $currentUser,
    protected EventDispatcherInterface $eventDispatcher,
    protected LoggerChannelInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('event_dispatcher'),
      $container->get('logger.channel.tool'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setInvoker(string|Invoker $invoker): static {
    $this->invoker = Invoker::from($invoker);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvoker(): ?Invoker {
    return $this->invoker;
  }

  /**
   * {@inheritdoc}
   */
  public function hasInvoker(): bool {
    return $this->invoker !== NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Overrides the trait implementation to dispatch a ToolInputTransformEvent,
   * allowing subscribers to transform the value based on the invoker identity
   * before it is stored.
   */
  public function setInputValue(string $name, $value): static {
    $definition = $this->getInputDefinition($name);
    $event = new ToolInputTransformEvent($name, $value, $this, $this->invoker, $definition);
    $this->eventDispatcher->dispatch($event);
    $value = $event->getValue();

    $this->doSetInputValue($name, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements(): void {
    // No requirements by default.
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): static {
    $this->executed = TRUE;
    $this->formattedResult = NULL;
    try {
      $args = $this->getExecutableValues();
      $this->result = $this->doExecute($args);
    }
    catch (\InvalidArgumentException | InputException $e) {
      // Expected failure types carry curated text (validation violations,
      // named-input messages) that invokers surface so callers can correct
      // their input.
      $this->result = ExecutableResult::failure(
        new TranslatableMarkup('Tool execution failed due to invalid input: @message', ['@message' => $e->getMessage()]),
        NULL,
      );
      return $this;
    }
    catch (\Throwable $e) {
      // Unexpected exceptions may carry internals (paths, service names,
      // query fragments) that must not flow into results read by end users
      // or LLMs. Log the detail and surface only the exception type.
      Error::logException($this->logger, $e);
      $this->result = ExecutableResult::failure(
        new TranslatableMarkup('Tool execution failed: @type. The full error has been logged.', ['@type' => get_class($e)]),
        NULL,
        FailureCategory::Runtime,
      );
      return $this;
    }
    // If result is successful, we need to check for provided context values.
    if ($this->result->isSuccess()) {
      // If the action provides context values, we need to set the values.
      if ($provided_definitions = $this->getOutputDefinitions()) {
        $this->outputs = [];
        foreach ($this->result->getContextValues() as $context_name => $value) {
          if (isset($provided_definitions[$context_name])) {
            $this->setOutputValue($context_name, $value);
          }
        }
      }
      $this->failOnOutputViolations();
    }
    return $this;
  }

  /**
   * Replaces a successful result with a failure when the outputs are invalid.
   *
   * The output definitions are the tool's contract with its caller. A
   * required output that was never set, or a value that fails its
   * definition, is a bug in the tool, not something the caller can fix by
   * changing its arguments, so the failure is categorized as Runtime and
   * the detail is logged against the tool.
   */
  private function failOnOutputViolations(): void {
    $violations = $this->validateOutputs();
    if ($violations->count() === 0) {
      return;
    }
    $this->logger->error('Tool @tool returned invalid outputs: @violations', [
      '@tool' => $this->getPluginId(),
      '@violations' => (string) $violations,
    ]);
    // The list's own string form prefixes every violation with its
    // typed-data root class, which is log detail, not caller detail
    // (#3583057): the result message carries the messages alone.
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = (string) $violation->getMessage();
    }
    $this->result = ExecutableResult::failure(
      new TranslatableMarkup("Tool execution failed:\n@violations", ['@violations' => implode("\n", $messages)]),
      NULL,
      FailureCategory::Runtime,
    );
  }

  /**
   * Executes the tool with the provided values.
   *
   * This method is called by the `execute()` method and should contain the
   * logic for executing the tool. It should return an ExecutableResult object.
   *
   * @param array $values
   *   The values to execute the tool with.
   *
   * @return \Drupal\tool\ExecutableResult
   *   The result of the tool execution.
   */
  abstract protected function doExecute(array $values): ExecutableResult;

  /**
   * {@inheritdoc}
   */
  public function access(?AccountInterface $account = NULL, bool $return_as_object = FALSE): bool|AccessResultInterface {
    $account = $account ?? $this->currentUser;
    /** @var \Drupal\tool\Tool\ToolDefinition $definition */
    $definition = $this->getPluginDefinition();
    $permission_access = ToolManager::checkPermission($definition, $account);
    if (!$permission_access->isAllowed()) {
      return $return_as_object ? $permission_access : FALSE;
    }
    try {
      $values = $this->getExecutableValues();
    }
    catch (\InvalidArgumentException) {
      // Fail closed on invalid input rather than letting the exception reach a
      // caller that expects an access result (#3582961). The reason stays
      // generic because the violations are TranslatableMarkup with render
      // placeholders in them; ::validateInputs() is where a caller gets the
      // detail. Not cacheable because ::setInputValue() can turn this into
      // a grant.
      $access = AccessResult::forbidden('Tool input validation failed.')
        ->setCacheMaxAge(0);
      return $return_as_object ? $access : FALSE;
    }

    $check = $this->checkAccess($values, $account, TRUE);
    // A bare bool carries no cacheability metadata, so it must not be cached.
    $result = $permission_access->andIf(is_bool($check) ? AccessResult::allowedIf($check)->setCacheMaxAge(0) : $check);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Checks whether an account may run this tool with the given values.
   *
   * By default, allows access when the tool declares a permission: a
   * permission-only tool needs no override. A tool with no declared
   * permission must override this method, or ::access() itself, to grant
   * access; discovery rejects a definition with no access rule at all. An
   * override decides on its own authority - ::access() has already
   * required the declared permission before calling this, so there is no
   * need to call the parent or to check that permission again; whatever an
   * override returns is combined with the permission result through
   * andIf().
   *
   * ::access() calls this only once the account satisfies the permission the
   * tool declares and the inputs have been resolved and validated, so the
   * values are valid by the time this runs and the account is already
   * resolved. An implementation is free to ignore the values and answer on
   * the account alone.
   *
   * @param array $values
   *   The resolved and validated executable values, keyed by input name.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for, already resolved by ::access().
   * @param bool $return_as_object
   *   Whether to return the result as an AccessResult object or a boolean.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   Returns an AccessResult object or a boolean indicating access status.
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    /** @var \Drupal\tool\Tool\ToolDefinition $definition */
    $definition = $this->getPluginDefinition();
    // ::access() has already required the declared permission; without a
    // declared permission this default has nothing to grant on.
    $access = AccessResult::allowedIf($definition->getPermission() !== NULL);
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getResultMessage(): TranslatableMarkup {
    if (!isset($this->result)) {
      throw new \BadMethodCallException('The action message cannot be retrieved until the action ::execute() method has been called.');
    }
    return $this->result->getMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getResultStatus(): bool {
    if (!isset($this->result)) {
      throw new \BadMethodCallException('The action status cannot be retrieved until the action ::execute() method has been called.');
    }
    return $this->result->isSuccess();
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(): ExecutableResult {
    if (!isset($this->result)) {
      throw new \BadMethodCallException('The action result cannot be retrieved until the action ::execute() method has been called.');
    }
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function hasResult(): bool {
    return isset($this->result);
  }

  /**
   * {@inheritdoc}
   */
  public function hasExecuted(): bool {
    return $this->executed;
  }

  /**
   * {@inheritdoc}
   */
  public function setFailureResult(TranslatableMarkup $message, FailureCategory $category = FailureCategory::Input): static {
    if (isset($this->result)) {
      throw new \LogicException('Cannot set a failure result: the tool already has a result.');
    }
    $this->result = ExecutableResult::failure($message, NULL, $category);
    $this->formattedResult = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedResult(): FormattedExecutableResult {
    if ($this->formattedResult !== NULL) {
      return $this->formattedResult;
    }
    $result = $this->getResult();

    // Dispatch per-output-value transform events.
    $context_values = $result->getContextValues();
    $transformed_values = [];
    $hints = [];
    $output_definitions = $this->getOutputDefinitions();
    foreach ($context_values as $name => $value) {
      // Result values without a declared output definition pass through
      // untransformed, mirroring the isset() filter in execute().
      if (!isset($output_definitions[$name])) {
        $transformed_values[$name] = $value;
        continue;
      }
      $output_event = new ToolOutputTransformEvent($name, $value, $this, $this->invoker, $output_definitions[$name]);
      $this->eventDispatcher->dispatch($output_event);
      $transformed_values[$name] = $output_event->getValue();
      if ($output_event->getMessages()) {
        $hints = array_merge($hints, $output_event->getMessages());
      }
    }

    return $this->formattedResult = new FormattedExecutableResult(
      $result,
      $transformed_values,
      $hints,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation) {
    /** @var \Drupal\tool\Tool\ToolDefinition $tool_definition */
    $tool_definition = $this->getPluginDefinition();
    return $tool_definition->getForm($operation);
  }

}
