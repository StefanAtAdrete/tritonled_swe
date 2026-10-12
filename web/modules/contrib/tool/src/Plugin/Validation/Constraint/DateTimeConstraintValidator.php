<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DateTime constraint.
 *
 * Ensures the value is a parseable ISO 8601 datetime string.
 */
class DateTimeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if ($value === NULL || $value === '') {
      return;
    }

    if (!is_string($value)) {
      $this->context->addViolation('The value must be a datetime string in ISO 8601 format.');
      return;
    }

    // Attempt to parse as a datetime. Bare Y-m-d is also valid for datetime
    // fields (they accept date-only input), so we allow that too.
    try {
      new \DateTimeImmutable($value);
    }
    catch (\Exception) {
      $this->context->addViolation('The value "@value" is not a valid ISO 8601 datetime (e.g., 2026-08-12T10:00:00+00:00 or 2026-08-12).', [
        '@value' => $value,
      ]);
    }
  }

}
