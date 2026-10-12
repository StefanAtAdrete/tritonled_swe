<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\IdenticalTo;
use Symfony\Component\Validator\Constraints\IdenticalToValidator;

/**
 * Exposes Symfony's IdenticalTo constraint as a constraint plugin.
 *
 * Core registers this plugin on 11.x but not on Drupal 10, so an input
 * declaring it would fail validation there with an unknown-plugin
 * violation; registering it here makes it available on every supported
 * core. Where core also registers it, the two are interchangeable — both
 * wrap the same Symfony constraint and validator.
 */
#[Constraint(
  id: 'IdenticalTo',
  label: new TranslatableMarkup('Identical to', [], ['context' => 'Validation']),
)]
class IdenticalToConstraint extends IdenticalTo {

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
    return IdenticalToValidator::class;
  }

}
