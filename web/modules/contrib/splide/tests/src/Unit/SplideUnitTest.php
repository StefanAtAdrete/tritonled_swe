<?php

namespace Drupal\Tests\splide\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\splide\Entity\Splide;
use Drupal\splide\SplideDefault;

/**
 * Testing \Drupal\splide\Entity\Splide.
 */
class SplideUnitTest extends UnitTestCase {

  /**
   * Tests for splide entity methods.
   */
  public function testSplideEntity() {
    $js_settings = SplideDefault::jsSettings();
    $this->assertArrayHasKey('perPage', $js_settings);

    $dependent_options = Splide::getDependentOptions();
    $this->assertArrayHasKey('arrows', $dependent_options);
  }

}
