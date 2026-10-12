<?php

declare(strict_types=1);

namespace Drupal\tool\Tool;

/**
 * Behaviors an invoker can ask Tool API to apply at its boundary.
 *
 * Other modules define their own enums for behaviors they subscribe to;
 * Invoker::has() accepts any enum case.
 */
enum InvokerCapability {

  // Content entities cross the boundary as handle strings.
  case EntitiesAsHandles;

}
