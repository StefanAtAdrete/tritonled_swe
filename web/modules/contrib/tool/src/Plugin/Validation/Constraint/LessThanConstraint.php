<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanValidator;

/**
 * Exposes Symfony's LessThan constraint as a constraint plugin.
 *
 * @see \Drupal\tool\Plugin\Validation\Constraint\GreaterThanConstraint
 */
#[Constraint(
  id: 'LessThan',
  label: new TranslatableMarkup('Less than', [], ['context' => 'Validation']),
)]
class LessThanConstraint extends LessThan {

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
    return LessThanValidator::class;
  }

}
