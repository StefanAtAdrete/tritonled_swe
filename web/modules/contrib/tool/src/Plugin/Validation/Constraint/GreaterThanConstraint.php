<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanValidator;

/**
 * Exposes Symfony's GreaterThan constraint as a constraint plugin.
 *
 * No supported core registers Symfony's comparison family completely
 * (11.x provides PositiveOrZero and IdenticalTo; Drupal 10 provides
 * neither), so this module registers every comparison constraint it maps
 * into schemas, e.g. constraints: ['GreaterThan' => ['value' => 0]].
 */
#[Constraint(
  id: 'GreaterThan',
  label: new TranslatableMarkup('Greater than', [], ['context' => 'Validation']),
)]
class GreaterThanConstraint extends GreaterThan {

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
    return GreaterThanValidator::class;
  }

}
