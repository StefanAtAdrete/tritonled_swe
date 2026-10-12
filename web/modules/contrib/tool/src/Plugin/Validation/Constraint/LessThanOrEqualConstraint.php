<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqualValidator;

/**
 * Exposes Symfony's LessThanOrEqual constraint as a constraint plugin.
 *
 * @see \Drupal\tool\Plugin\Validation\Constraint\GreaterThanConstraint
 */
#[Constraint(
  id: 'LessThanOrEqual',
  label: new TranslatableMarkup('Less than or equal', [], ['context' => 'Validation']),
)]
class LessThanOrEqualConstraint extends LessThanOrEqual {

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
    return LessThanOrEqualValidator::class;
  }

}
