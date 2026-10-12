<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Marks a datetime_iso8601 property as a full datetime (Y-m-d\TH:i:s storage).
 *
 * This is a semantic marker constraint used by the Tool API's normalizer
 * and coercion subscriber to distinguish full datetime fields from date-only
 * fields. Both use the same 'datetime_iso8601' typed data type, but require
 * different JSON Schema formats:
 *
 * - DateTime: schema emits format:'date-time', coercion normalizes to ISO 8601.
 * - DateOnly: schema emits format:'date', coercion preserves bare Y-m-d.
 * - Neither: schema omits format entirely.
 *
 * This constraint does not perform validation itself — the underlying field
 * type handles format validation.
 */
#[Constraint(
  id: 'DateTime',
  label: new TranslatableMarkup('Date time', [], ['context' => 'Validation']),
)]
class DateTimeConstraint extends SymfonyConstraint {

}
