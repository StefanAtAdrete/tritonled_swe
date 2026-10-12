<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

/**
 * Marks exceptions thrown while resolving or storing a handle.
 *
 * A bad handle (malformed, missing, or resolving to the wrong value type) is
 * always the invoker's fault to fix by sending a different argument, never a
 * runtime failure. Connectors catch this interface to classify these
 * failures as \Drupal\tool\FailureCategory::Input regardless of which
 * concrete exception class was thrown (#3582993).
 *
 * Extends \Throwable, not just a plain marker: PHP refuses to compile a
 * `catch` clause typed to an interface unless that interface is (or
 * extends) \Throwable.
 */
interface HandleExceptionInterface extends \Throwable {
}
