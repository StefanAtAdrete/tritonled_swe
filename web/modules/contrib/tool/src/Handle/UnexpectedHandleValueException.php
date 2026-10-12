<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

/**
 * Thrown when a handle resolves to an unexpected value type.
 */
final class UnexpectedHandleValueException extends \UnexpectedValueException implements HandleExceptionInterface {
}
