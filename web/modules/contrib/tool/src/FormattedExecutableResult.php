<?php

namespace Drupal\tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Represents a tool result after the invoker transform pipeline.
 *
 * This is returned by getFormattedResult() and carries the transformed
 * context values, the original message, and any hints added by
 * output transform subscribers.
 */
final readonly class FormattedExecutableResult implements FormattedToolResultInterface {

  /**
   * Constructs a FormattedExecutableResult.
   *
   * @param \Drupal\tool\ExecutableResult $originalResult
   *   The original untransformed result.
   * @param array $contextValues
   *   The transformed context values.
   * @param string[] $hints
   *   Hints providing additional context about transformations applied
   *   (e.g., "Use `handle:uuid` to reference this entity").
   */
  public function __construct(
    private ExecutableResult $originalResult,
    private array $contextValues = [],
    private array $hints = [],
  ) {
  }

  /**
   * Indicates if the operation was successful.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function isSuccess(): bool {
    return $this->originalResult->isSuccess();
  }

  /**
   * Gets the result message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The original result message.
   */
  public function getMessage(): TranslatableMarkup {
    return $this->originalResult->getMessage();
  }

  /**
   * Gets the transformed context values.
   *
   * @return array
   *   The array of transformed values, keyed by context name.
   */
  public function getContextValues(): array {
    return $this->contextValues;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailureCategory(): ?FailureCategory {
    return $this->originalResult->getFailureCategory();
  }

  /**
   * Gets hints added by transform subscribers.
   *
   * Hints provide additional guidance for the invoker about how to
   * interpret or use the transformed values.
   *
   * @return string[]
   *   The hints array.
   */
  public function getHints(): array {
    return $this->hints;
  }

  /**
   * Gets the original untransformed result.
   *
   * @return \Drupal\tool\ExecutableResult
   *   The raw result as produced by the tool.
   */
  public function getOriginalResult(): ExecutableResult {
    return $this->originalResult;
  }

}
