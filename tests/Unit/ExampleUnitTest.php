<?php

namespace Drupal\Tests\tritonled\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Example unit test for TritonLED.
 *
 * @group tritonled
 * @group unit
 */
class ExampleUnitTest extends UnitTestCase {

  /**
   * Test basic arithmetic.
   *
   * This is a simple example to verify PHPUnit setup works.
   */
  public function testBasicArithmetic() {
    $this->assertEquals(4, 2 + 2);
    $this->assertNotEquals(5, 2 + 2);
  }

  /**
   * Test string operations.
   */
  public function testStringOperations() {
    $sku = "ORBIT-20W-3000K";
    
    $this->assertStringContainsString("ORBIT", $sku);
    $this->assertStringContainsString("20W", $sku);
    $this->assertStringContainsString("3000K", $sku);
  }

  /**
   * Test array operations.
   */
  public function testArrayOperations() {
    $attributes = [
      'watt' => '20W',
      'cct' => '3000K',
      'cri' => '>80',
    ];

    $this->assertArrayHasKey('watt', $attributes);
    $this->assertEquals('20W', $attributes['watt']);
    $this->assertCount(3, $attributes);
  }

  /**
   * Test exception handling.
   */
  public function testExceptionThrown() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('SKU cannot be empty');

    // Example validation function
    $this->validateSku('');
  }

  /**
   * Mock SKU validation.
   */
  private function validateSku($sku) {
    if (empty($sku)) {
      throw new \InvalidArgumentException('SKU cannot be empty');
    }
    return TRUE;
  }

}
