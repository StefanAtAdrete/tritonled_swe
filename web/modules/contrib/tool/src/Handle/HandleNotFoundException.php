<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

/**
 * Thrown when a valid handle is missing or has expired.
 */
final class HandleNotFoundException extends \RuntimeException implements HandleExceptionInterface {
}
