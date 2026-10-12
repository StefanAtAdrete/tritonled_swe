<?php

declare(strict_types=1);

namespace Drupal\tool\Drush\Commands;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\tool\Drush\DrushInvoker;
use Drupal\tool\Drush\RunErrorType;
use Drupal\tool\Tool\ToolManager;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Run a tool with the specified inputs.
 */
final class ToolRunCommand extends DrushCommands {

  use AutowireTrait;
  use ToolOutputTrait;

  /**
   * Constructs a ToolRunCommand object.
   *
   * @param \Drupal\tool\Tool\ToolManager $toolManager
   *   The tool plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $accountSwitcher
   *   The account switcher service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user, reflecting the account switch.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.tool')]
    private readonly ToolManager $toolManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountSwitcherInterface $accountSwitcher,
    #[Autowire(service: 'current_user')]
    private readonly AccountProxyInterface $currentUser,
  ) {
    parent::__construct();
  }

  /**
   * Run a tool with the specified inputs.
   *
   * @param string $tool_id
   *   The tool plugin ID.
   * @param array $options
   *   Command options.
   */
  #[Command(name: 'tool:run', aliases: ['trun'])]
  #[Argument(name: 'tool_id', description: 'The tool plugin ID.')]
  #[Option(name: 'input', description: 'Input values as JSON or key=value pairs. Can be used multiple times.')]
  #[Option(name: 'json', description: 'Output result as JSON.')]
  #[Option(name: 'uid', description: 'Run as the given user ID. Defaults to 0 (anonymous); pass --uid=1 explicitly for superuser execution.')]
  #[Usage(name: 'drush tool:run send_email --uid=2 --input=\'{"recipient":"test@example.com","subject":"Test","message":"Hello"}\'', description: 'Run send_email as user 2 with JSON input.')]
  #[Usage(name: 'drush tool:run system_status', description: 'Run system_status tool.')]
  #[Usage(name: 'drush tool:run send_email --uid=2 --input=recipient=test@example.com --input=subject=Test --input=message=Hello', description: 'Run send_email as user 2 with key=value inputs.')]
  public function runTool(
    string $tool_id,
    array $options = [
      'input' => [],
      'json' => FALSE,
      'uid' => NULL,
    ],
  ): int {
    if (!$this->toolManager->hasDefinition($tool_id)) {
      $this->writeError("Tool '$tool_id' not found.", $options['json'], RunErrorType::ToolNotFound);
      return self::EXIT_FAILURE;
    }

    // Validate options before switching accounts or executing the tool.
    try {
      $uid = $this->parseUid($options['uid'] ?? NULL);
      $inputValues = $this->parseInputValues($options['input']);
    }
    catch (\InvalidArgumentException $e) {
      $this->writeError($e->getMessage(), $options['json'], RunErrorType::InvalidOption);
      return self::EXIT_FAILURE;
    }

    if ($uid === 0) {
      $account = new AnonymousUserSession();
    }
    else {
      // Switch to the requested user for execution.
      /** @var \Drupal\user\UserInterface|null $account */
      $account = $this->entityTypeManager
        ->getStorage('user')
        ->load($uid);
      if (!$account) {
        $this->writeError("User '$uid' not found.", $options['json'], RunErrorType::UserNotFound);
        return self::EXIT_FAILURE;
      }
      if ($account->isBlocked()) {
        $this->writeError("User '$uid' is blocked.", $options['json'], RunErrorType::UserBlocked);
        return self::EXIT_FAILURE;
      }
    }

    $this->accountSwitcher->switchTo($account);

    try {
      return $this->executeTool($tool_id, $inputValues, $options);
    }
    finally {
      $this->accountSwitcher->switchBack();
    }
  }

  /**
   * Parse and validate a user ID option value.
   *
   * @param mixed $rawUid
   *   The raw option value.
   *
   * @return int
   *   The validated user ID.
   *
   * @throws \InvalidArgumentException
   *   When the value is not a non-negative integer user ID.
   */
  private function parseUid(mixed $rawUid): int {
    // Drush uses NULL for omission and FALSE for an explicit --uid=0.
    if ($rawUid === NULL || $rawUid === FALSE) {
      return 0;
    }
    if (is_int($rawUid) || is_string($rawUid)) {
      $uid = filter_var($rawUid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
      if ($uid !== FALSE) {
        return $uid;
      }
    }

    $display = is_scalar($rawUid) && !is_bool($rawUid) ? (string) $rawUid : var_export($rawUid, TRUE);
    throw new \InvalidArgumentException("Invalid --uid value '$display': expected a non-negative integer user ID.");
  }

  /**
   * Builds the message reported for a denial, from either access stage.
   *
   * The declared-permission pre-check and access() report the same way, so
   * a caller cannot tell which stage denied (#3583077).
   *
   * @param string $tool_id
   *   The tool plugin ID.
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The denying access result.
   * @param array $options
   *   Command options.
   *
   * @return string
   *   The denial message.
   */
  private function accessDeniedMessage(string $tool_id, AccessResultInterface $access, array $options): string {
    $reason = $access instanceof AccessResultReasonInterface ? $access->getReason() : NULL;
    $message = $reason
      ? "Access denied for tool '$tool_id': $reason"
      : "Access denied for tool '$tool_id'.";
    // Without --uid the command runs as anonymous, which the caller may not
    // know. An explicit --uid=0 is a deliberate choice and needs no hint.
    if (($options['uid'] ?? NULL) === NULL) {
      $message .= ' Ran as anonymous (uid 0); pass --uid=<id> to run as a user.';
    }
    return $message;
  }

  /**
   * Execute the tool after user switching.
   *
   * @param string $tool_id
   *   The tool plugin ID.
   * @param array $inputValues
   *   Parsed input values.
   * @param array $options
   *   Command options.
   */
  private function executeTool(string $tool_id, array $inputValues, array $options): int {
    // Check the declared permission before instantiating the tool or
    // touching any caller input, so an account without it is denied before
    // transforms and refiners run on its values (#3583077). access() below
    // still gates execution.
    $permission_access = ToolManager::checkPermission($this->toolManager->getDefinition($tool_id), $this->currentUser);
    if (!$permission_access->isAllowed()) {
      $this->writeError($this->accessDeniedMessage($tool_id, $permission_access, $options), $options['json'], RunErrorType::AccessDenied);
      return self::EXIT_FAILURE;
    }

    // Create tool instance.
    /** @var \Drupal\tool\Tool\ToolInterface $tool */
    $tool = $this->toolManager->createInstance($tool_id, [], DrushInvoker::create());

    // Set input values.
    foreach ($inputValues as $name => $value) {
      try {
        $tool->setInputValue($name, $value);
      }
      catch (\Exception $e) {
        $this->writeError("Error setting input '$name': " . $e->getMessage(), $options['json'], RunErrorType::InvalidInput);
        return self::EXIT_FAILURE;
      }
    }

    // Validate before the access check. access() fails closed with a generic
    // reason on invalid input (#3582961), so checking it first would report a
    // missing or invalid input as a permission problem, and the caller would
    // give up instead of correcting the input.
    $violations = $tool->validateInputs();
    if ($violations->count() > 0) {
      $messages = [];
      foreach ($violations as $violation) {
        $messages[] = PlainTextOutput::renderFromHtml((string) $violation->getMessage());
      }
      $this->writeError("Input validation failed for tool '$tool_id': " . implode(' ', $messages), $options['json'], RunErrorType::InvalidInput);
      return self::EXIT_FAILURE;
    }

    // Check access, keeping the reason the tool gives for a denial.
    $access = $tool->access(return_as_object: TRUE);
    if (!$access->isAllowed()) {
      $this->writeError($this->accessDeniedMessage($tool_id, $access, $options), $options['json'], RunErrorType::AccessDenied);
      return self::EXIT_FAILURE;
    }

    if (!$options['json']) {
      $this->io()->writeln("<info>Running tool: $tool_id</info>");
      $this->io()->writeln('');
    }

    // Execute the tool.
    try {
      $tool->execute();
    }
    catch (\Exception $e) {
      $this->writeError('Execution failed: ' . $e->getMessage(), $options['json'], RunErrorType::ExecutionFailed);
      return self::EXIT_FAILURE;
    }

    // Get result.
    $success = $tool->getResultStatus();
    // Result messages are HTML-safe markup; the terminal needs plain text,
    // or escaped placeholders print as entities (#3583042).
    $message = PlainTextOutput::renderFromHtml((string) $tool->getResultMessage());
    // The formatted result is what leaves the process: timestamps, decimals,
    // dates, and maps keyed from zero spelled the way the advertised schema
    // describes them. A failed tool carries no output values.
    $outputs = $tool->getFormattedResult()->getContextValues();

    if ($options['json']) {
      $result = [
        'success' => $success,
        'message' => $message,
        'outputs' => $this->serializeOutputs($outputs),
      ];
      $this->io()->writeln(Json::encode($result));
    }
    else {
      if ($success) {
        $this->io()->writeln('<info>Success:</info> ' . $message);
      }
      else {
        $this->io()->writeln('<error>Failed:</error> ' . $message);
      }

      if (!empty($outputs)) {
        $this->io()->writeln('');
        $this->io()->writeln('<info>Outputs:</info>');
        foreach ($outputs as $name => $value) {
          $this->io()->writeln("  <comment>$name:</comment> " . $this->formatOutputValue($value));
        }
      }
    }

    return $success ? self::EXIT_SUCCESS : self::EXIT_FAILURE;
  }

  /**
   * Parse input values from command options.
   *
   * @param array|string $inputs
   *   The input option values.
   *
   * @return array
   *   Parsed input values.
   *
   * @throws \InvalidArgumentException
   *   When an input token is not a JSON object or a name=value pair.
   */
  private function parseInputValues(array|string $inputs): array {
    if ($inputs === [] || $inputs === '') {
      return [];
    }

    // Ensure array.
    if (!is_array($inputs)) {
      $inputs = [$inputs];
    }

    $values = [];

    foreach ($inputs as $input) {
      if (!is_string($input)) {
        throw new \InvalidArgumentException('Invalid input: expected a JSON object or a name=value pair.');
      }

      // Try to parse as JSON first.
      $isJson = TRUE;
      try {
        $decoded = json_decode($input, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\JsonException) {
        $decoded = NULL;
        $isJson = FALSE;
      }

      // Valid JSON null is not the same as a decoding failure.
      if ($isJson) {
        if (is_array($decoded) && str_starts_with(ltrim($input), '{')) {
          foreach ($decoded as $name => $value) {
            $this->addInputValue($values, $name, $value);
          }
          continue;
        }
        throw new \InvalidArgumentException("Invalid input '$input': JSON input must be an object containing named input values.");
      }

      // Parse as key=value.
      if (!str_contains($input, '=')) {
        throw new \InvalidArgumentException("Invalid input '$input': expected JSON or a name=value pair.");
      }
      [$key, $value] = explode('=', $input, 2);
      $key = trim($key);
      $value = trim($value);

      // Try to decode value as JSON for complex types.
      try {
        $decodedValue = json_decode($value, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\JsonException) {
        $decodedValue = $value;
      }
      $this->addInputValue($values, $key, $decodedValue);
    }

    return $values;
  }

  /**
   * Add a parsed value after validating its input name.
   *
   * @param array $values
   *   Previously parsed input values.
   * @param int|string $name
   *   The parsed input name.
   * @param mixed $value
   *   The parsed input value.
   *
   * @throws \InvalidArgumentException
   *   When the input name is empty or PHP would convert it to an integer key.
   */
  private function addInputValue(array &$values, int|string $name, mixed $value): void {
    // Check whether PHP would convert the input name to an integer array key.
    $normalizedName = array_key_first([$name => NULL]);
    if (!is_string($name) || $name === '' || !is_string($normalizedName)) {
      throw new \InvalidArgumentException("Invalid input name '$name': names must be non-empty and must not be integer keys.");
    }
    $values[$name] = $value;
  }

  /**
   * Serialize output values for JSON output.
   *
   * @param array $outputs
   *   The output values.
   *
   * @return array
   *   Serialized outputs.
   */
  private function serializeOutputs(array $outputs): array {
    $result = [];
    foreach ($outputs as $name => $value) {
      $value = $this->referenceEntities($value);
      // A map, which Json::encode() writes as an object.
      if ($value instanceof \ArrayObject) {
        $result[$name] = $value;
      }
      elseif (is_object($value)) {
        if (method_exists($value, 'toArray')) {
          $result[$name] = $value->toArray();
        }
        elseif (method_exists($value, '__toString')) {
          $result[$name] = (string) $value;
        }
        else {
          $result[$name] = get_class($value);
        }
      }
      else {
        $result[$name] = $value;
      }
    }
    return $result;
  }

  /**
   * Write an error message, respecting the --json flag.
   *
   * @param string $message
   *   The error message.
   * @param bool $json
   *   Whether to output as JSON.
   * @param \Drupal\tool\Drush\RunErrorType $type
   *   Why the command failed, carried as error_type in the JSON output.
   */
  private function writeError(string $message, bool $json, RunErrorType $type): void {
    if ($json) {
      $this->io()->writeln(Json::encode([
        'success' => FALSE,
        'error' => $message,
        'error_type' => $type->value,
      ]));
    }
    else {
      $this->io()->writeln("<error>$message</error>");
    }
  }

}
