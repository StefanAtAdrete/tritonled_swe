<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

/**
 * Thrown when a handle string is malformed.
 */
final class InvalidHandleException extends \InvalidArgumentException implements HandleExceptionInterface {
}
