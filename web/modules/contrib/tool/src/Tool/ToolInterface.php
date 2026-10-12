<?php

declare(strict_types=1);

namespace Drupal\tool\Tool;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\ExecutableResultInterface;
use Drupal\tool\FailureCategory;
use Drupal\tool\FormattedToolResultInterface;
use Drupal\tool\ToolResultInterface;
use Drupal\tool\TypedInputsInterface;
use Drupal\tool\TypedOutputsInterface;

/**
 * Interface for tool plugins.
 *
 * Deliberately not cacheable (#3582965): tool executions are per-request
 * with per-call arguments, and no consumer caches tool results. If result
 * caching becomes a feature, cacheability should return as a designed
 * contract rather than the former always-empty stubs.
 *
 * Still extends the deprecated ExecutableResultInterface shell on purpose,
 * so instanceof checks keep working for one beta cycle (#3582964); the
 * corresponding phpstan suppression lives in phpstan.neon.
 */
interface ToolInterface extends ExecutableResultInterface, TypedInputsInterface, TypedOutputsInterface, PluginWithFormsInterface {

  /**
   * Sets the invoker for this tool.
   *
   * An invoker represents the context in which a tool is being called (e.g.,
   * an AI Agent, MCP server, or deterministic caller). The invoker identity
   * is carried on pre/post-invocation events, allowing external modules to
   * subscribe and conditionally apply input/output transformations.
   *
   * @param string|\Drupal\tool\Tool\Invoker $invoker
   *   The invoker identifier, or an Invoker that also declares what the
   *   calling context needs from Tool API.
   *
   * @return $this
   */
  public function setInvoker(string|Invoker $invoker): static;

  /**
   * Gets the invoker for this tool.
   *
   * @return \Drupal\tool\Tool\Invoker|null
   *   The invoker, or NULL if no invoker has been set.
   */
  public function getInvoker(): ?Invoker;

  /**
   * Checks whether an invoker has been set.
   *
   * @return bool
   *   TRUE if an invoker is set, FALSE otherwise.
   */
  public function hasInvoker(): bool;

  /**
   * Executes the tool with its resolved input values.
   *
   * Declared here rather than inherited from core's ExecutableInterface:
   * that interface carries a scheduled signature change with subject-object
   * semantics that do not fit typed-input tools (#3582964), and this
   * declaration gains a real return type core's could never have.
   *
   * @return static
   *   The tool instance, with a result recorded.
   */
  public function execute(): static;

  /**
   * Gets the result of the execution.
   *
   * @return \Drupal\tool\ToolResultInterface
   *   The result of the execution.
   */
  public function getResult(): ToolResultInterface;

  /**
   * Gets the status of the result.
   *
   * @return bool
   *   TRUE if the execution was successful, FALSE otherwise.
   */
  public function getResultStatus(): bool;

  /**
   * Gets the message associated with the result.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The message associated with the result.
   */
  public function getResultMessage(): TranslatableMarkup;

  /**
   * Gets the formatted result, transformed for the invoker.
   *
   * This method applies output transformations based on the invoker identity:
   * 1. Per-output-value transforms (e.g., entity to token).
   * 2. Full result transforms (e.g., encoding/reducing for LLMs).
   *
   * Use getResult() to access the raw, untransformed result.
   *
   * @return \Drupal\tool\FormattedToolResultInterface
   *   The transformed result ready for the invoker.
   */
  public function getFormattedResult(): FormattedToolResultInterface;

  /**
   * Checks whether a result is available.
   *
   * Unlike getResult(), this never throws, so callers can test for the
   * no-result state without exception-based control flow. A result exists
   * after execute() has run, or after setFailureResult() has recorded a
   * failure without execution — use hasExecuted() to distinguish the two.
   *
   * @return bool
   *   TRUE if a result is available.
   */
  public function hasResult(): bool;

  /**
   * Checks whether execute() has been called on this instance.
   *
   * A tool can have a result without having executed (via
   * setFailureResult()); this reports actual execution, so consumers such
   * as retry logic or telemetry can tell the difference.
   *
   * @return bool
   *   TRUE if execute() has been called.
   */
  public function hasExecuted(): bool;

  /**
   * Records a failure result without executing the tool.
   *
   * For invokers whose input stage fails before execute() can be called
   * (invalid input, access denial), so consumers reading the result always
   * receive the failure rather than a BadMethodCallException.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The failure message.
   * @param \Drupal\tool\FailureCategory $category
   *   The failure category. Defaults to Input; see
   *   \Drupal\tool\ExecutableResult::__construct() for why.
   *
   * @return static
   *   The tool instance.
   *
   * @throws \LogicException
   *   If the tool already has a result.
   */
  public function setFailureResult(TranslatableMarkup $message, FailureCategory $category = FailureCategory::Input): static;

  /**
   * Checks that requirements for this tool are met.
   *
   * Override this method to verify static prerequisites such as API keys or
   * Drupal configuration values being present. This is not intended to
   * validate runtime state (e.g. stale credentials, network availability).
   *
   * This is a configuration-time signal, not a runtime gate (#3582975):
   * configuration and curation UIs call it to show an unmet-requirements
   * indicator and to prevent a tool from being added to something, but
   * execute() and access() deliberately never consult it. Enforcing it at
   * execution time would invite per-call runtime health checks (external
   * service status) into a hook meant for deployment readiness, and those
   * would then run on every invocation.
   *
   * @throws \Drupal\tool\Exception\RequirementsException
   *   Thrown when a required configuration dependency is absent, with a
   *   message describing what is missing.
   */
  public function checkRequirements(): void;

  /**
   * Checks whether an account may run this tool with its current input values.
   *
   * This is the execution-time gate. It answers one question: may this
   * account run this invocation, with the values currently set on the tool.
   * The answer depends on those values, so it can change after
   * ::setInputValue() is called.
   *
   * It is deliberately not an answer to "may this account use this tool at
   * all, before any values exist". A form deciding whether to build, a
   * picker listing tools, or a catalog filtering them are asking that
   * second question. \Drupal\tool\Tool\ToolManager::checkPermission()
   * answers it from the permission the tool declares, without instantiating
   * the tool; the tool's own access logic can still deny at execution time.
   * For an account that satisfies that permission, calling this method
   * before the required inputs are set returns the validation denial
   * described below, not an access verdict.
   *
   * The checks run in this order, and the first one that does not allow
   * access decides the result: the permission the tool declares, then input
   * resolution and validation, then the tool's own access logic. When
   * validation fails, access is denied with a generic reason and the
   * violations are dropped (#3582961).
   *
   * ToolBase's default checkAccess() checks the declared permission and
   * returns neutral when there is none, so a tool must declare a permission
   * or override checkAccess() to grant access. An override can impose
   * additional restrictions: it must itself allow access, and satisfying the
   * declared permission alone is not enough.
   *
   * That means a denial from this method has two possible meanings, and
   * callers cannot tell them apart from the result alone: the account may
   * not do this, or the arguments are wrong and could be corrected. Call
   * ::validateInputs() first to separate the two. A caller that skips it
   * reports an argument problem to the user, or to a model, as a permission
   * problem.
   *
   * \Drupal\tool\Tool\ToolBase implements this order. A subclass that
   * overrides this method bypasses the declared permission unless it calls
   * parent::access(), or checks the permission itself through
   * \Drupal\tool\Tool\ToolManager::checkPermission().
   *
   * ::checkRequirements() is not consulted here; it is a configuration-time
   * signal, not a runtime gate.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check access for. If NULL, current user should be used.
   * @param bool $return_as_object
   *   Whether to return the access result as an object.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   TRUE, or an allowed access result, when the account may run the tool
   *   with the values currently set. FALSE, or a result that is not allowed,
   *   when it may not, or when those values fail validation.
   *
   * @see \Drupal\tool\Tool\ToolManager::checkPermission()
   * @see \Drupal\tool\TypedInputsInterface::validateInputs()
   * @see \Drupal\tool\TypedInputsInterface::getExecutableValues()
   */
  public function access(?AccountInterface $account = NULL, bool $return_as_object = FALSE): bool|AccessResultInterface;

}
