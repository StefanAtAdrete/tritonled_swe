<?php

namespace Drupal\tool;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The value contract for a tool execution result.
 *
 * Implemented by the final ExecutableResult value object; consumers should
 * type against this interface so results can be decorated or mocked
 * (#3582964).
 */
interface ToolResultInterface {

  /**
   * Indicates if the operation was successful.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function isSuccess(): bool;

  /**
   * Gets the descriptive message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The result message.
   */
  public function getMessage(): TranslatableMarkup;

  /**
   * Gets any returned context values.
   *
   * @return array
   *   The array of values, keyed by context name. Always an array; a tool
   *   without outputs reports [].
   */
  public function getContextValues(): array;

  /**
   * Gets the failure category, if this result is a failure.
   *
   * Tells a caller whether a retry with different arguments could fix the
   * failure. See \Drupal\tool\FailureCategory for the categories.
   *
   * @return \Drupal\tool\FailureCategory|null
   *   The failure category, or NULL when isSuccess() is TRUE.
   */
  public function getFailureCategory(): ?FailureCategory;

}
