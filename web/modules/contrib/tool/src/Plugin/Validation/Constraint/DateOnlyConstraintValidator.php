<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DateOnly constraint.
 *
 * Ensures the value is a valid date in Y-m-d format.
 */
class DateOnlyConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if ($value === NULL || $value === '') {
      return;
    }

    if (!is_string($value)) {
      $this->context->addViolation('The value must be a date string in Y-m-d format.');
      return;
    }

    // Must match Y-m-d pattern.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
      $this->context->addViolation('The value "@value" is not in the required Y-m-d date format (e.g., 2026-08-12).', [
        '@value' => $value,
      ]);
      return;
    }

    // Validate it's an actual date (not 2026-02-30).
    $parts = explode('-', $value);
    if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
      $this->context->addViolation('The value "@value" is not a valid date.', [
        '@value' => $value,
      ]);
    }
  }

}
