<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tool\FailureCategory;

/**
 * Tests the FailureCategory enum.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\FailureCategory
 */
class FailureCategoryTest extends UnitTestCase {

  /**
   * Tests only Input is reported as correctable.
   *
   * @covers ::isCorrectable
   */
  public function testOnlyInputIsCorrectable(): void {
    $this->assertTrue(FailureCategory::Input->isCorrectable());
    $this->assertFalse(FailureCategory::Access->isCorrectable());
    $this->assertFalse(FailureCategory::Runtime->isCorrectable());
  }

}
