<?php

declare(strict_types=1);

namespace Drupal\tool_test;

/**
 * Capabilities a module outside Tool API can define for its own subscribers.
 */
enum TestInvokerCapability {

  // String outputs cross the boundary in upper case.
  case UppercaseOutputs;

}
