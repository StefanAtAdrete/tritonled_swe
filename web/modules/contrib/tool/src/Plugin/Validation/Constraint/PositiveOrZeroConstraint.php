<?php

declare(strict_types=1);

namespace Drupal\tool\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqualValidator;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Exposes Symfony's PositiveOrZero constraint as a constraint plugin.
 *
 * Core registers this plugin on 11.x but not on Drupal 10, so an input
 * declaring it would fail validation there with an unknown-plugin
 * violation; registering it here makes it available on every supported
 * core. Where core also registers it, the two are interchangeable — both
 * wrap the same Symfony constraint and validator.
 */
#[Constraint(
  id: 'PositiveOrZero',
  label: new TranslatableMarkup('Positive or zero', [], ['context' => 'Validation']),
)]
class PositiveOrZeroConstraint extends PositiveOrZero {

  /**
   * Constructs the constraint from plugin configuration.
   *
   * Cores without named-argument support (Drupal 10) pass the whole options
   * array as the first positional argument, so it is accepted and unpacked
   * here; cores with it (11.x) spread the options as named arguments.
   */
  #[HasNamedArguments]
  public function __construct(mixed $message = NULL) {
    if (is_array($message)) {
      $message = $message['message'] ?? NULL;
    }
    parent::__construct(message: $message);
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy(): string {
    return GreaterThanOrEqualValidator::class;
  }

}
