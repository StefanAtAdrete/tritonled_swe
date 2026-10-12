<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

/**
 * Result of an output handle transformation.
 *
 * Carries the transformed value and an optional hint for the invoker.
 */
final readonly class HandleTransformResult {

  /**
   * Constructs a HandleTransformResult.
   *
   * @param mixed $value
   *   The transformed value (e.g., handle string).
   * @param string|null $hint
   *   Optional hint for the invoker about the handle.
   */
  public function __construct(
    public mixed $value,
    public ?string $hint = NULL,
  ) {
  }

}
