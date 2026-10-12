<?php

namespace Drupal\Tests\fences\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for fences.
 *
 * @group fences
 */
class FencesGenericTest extends GenericModuleTestBase {

  /**
   * {@inheritDoc}
   */
  protected function assertHookHelp(string $module): void {
    // Don't do anything here. We intend to implement hook_help() differently.
  }

}
