<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqualValidator;

/**
 * Exposes Symfony's GreaterThanOrEqual constraint as a constraint plugin.
 *
 * @see \Drupal\tool\Plugin\Validation\Constraint\GreaterThanConstraint
 */
#[Constraint(
  id: 'GreaterThanOrEqual',
  label: new TranslatableMarkup('Greater than or equal', [], ['context' => 'Validation']),
)]
class GreaterThanOrEqualConstraint extends GreaterThanOrEqual {

  /**
   * Constructs the constraint from plugin configuration.
   */
  #[HasNamedArguments]
  public function __construct(mixed $value = NULL, ?string $message = NULL) {
    parent::__construct(value: $value, message: $message);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return GreaterThanOrEqualValidator::class;
  }

}
