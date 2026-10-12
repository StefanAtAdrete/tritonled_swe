<?php

declare(strict_types=1);

namespace Drupal\tool;

/**
 * Categorizes why a tool execution failed.
 *
 * A caller such as an LLM function-calling invoker needs to know whether a
 * retry with different arguments could fix the failure. Input problems can
 * be fixed that way. Access denials and runtime errors cannot (#3582993).
 */
enum FailureCategory {

  // The input values were invalid, missing, or otherwise unsuitable.
  case Input;

  // The current user does not have permission to run the operation.
  case Access;

  // The operation failed for a reason unrelated to the input values.
  case Runtime;

  /**
   * Checks whether a retry with different arguments could fix the failure.
   *
   * @return bool
   *   TRUE only for the Input category.
   */
  public function isCorrectable(): bool {
    return $this === self::Input;
  }

}
