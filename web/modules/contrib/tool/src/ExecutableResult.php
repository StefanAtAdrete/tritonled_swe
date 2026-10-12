<?php

namespace Drupal\tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Represents the result of executing an operation.
 */
final readonly class ExecutableResult implements ToolResultInterface {

  /**
   * The returned context values, keyed by context name.
   */
  private array $contextValues;

  /**
   * OperationResult constructor.
   *
   * @param bool $success
   *   Indicates success or failure.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   A message or description.
   * @param array|null $contextValues
   *   Optional extra context. NULL is accepted and treated as no values, so
   *   getContextValues() always returns an array (#3582962).
   * @param \Drupal\tool\FailureCategory $failureCategory
   *   The failure category. Ignored when $success is TRUE. Defaults to
   *   Input so a caller that constructs a failure directly, without
   *   thinking about categorization, keeps the schema-attached behavior
   *   that existed before #3582993 introduced this parameter.
   */
  public function __construct(
    private bool $success,
    private TranslatableMarkup $message,
    ?array $contextValues = [],
    private FailureCategory $failureCategory = FailureCategory::Input,
  ) {
    $this->contextValues = $contextValues ?? [];
  }

  /**
   * Indicates if the operation was successful.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function isSuccess(): bool {
    return $this->success;
  }

  /**
   * Gets the descriptive message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The result message.
   */
  public function getMessage(): TranslatableMarkup {
    return $this->message;
  }

  /**
   * Gets any returned context.
   *
   * @return array
   *   The array of values, keyed by context name.
   */
  public function getContextValues(): array {
    return $this->contextValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailureCategory(): ?FailureCategory {
    return $this->success ? NULL : $this->failureCategory;
  }

  /**
   * Creates a successful result.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   A message or description.
   * @param ?array $context_values
   *   Optional array of values keyed by context name.
   *
   * @return static
   */
  public static function success(TranslatableMarkup $message, ?array $context_values = []): static {
    return new self(TRUE, $message, $context_values);
  }

  /**
   * Creates a failed result.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   A message or description.
   * @param ?array $context_values
   *   Optional array of values keyed by context name.
   * @param \Drupal\tool\FailureCategory $category
   *   The failure category. See the constructor for the default.
   *
   * @return static
   */
  public static function failure(TranslatableMarkup $message, ?array $context_values = [], FailureCategory $category = FailureCategory::Input): static {
    return new self(FALSE, $message, $context_values, $category);
  }

}
