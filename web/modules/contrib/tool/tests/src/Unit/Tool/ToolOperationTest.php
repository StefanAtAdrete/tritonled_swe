<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit\Tool;

use Drupal\Tests\UnitTestCase;
use Drupal\tool\Tool\ToolOperation;

/**
 * Tests the ToolOperation semantics matrix.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\Tool\ToolOperation
 */
class ToolOperationTest extends UnitTestCase {

  /**
   * Tests the modifying/idempotent matrix, including Transform (#3582962).
   *
   * @covers ::isModifying
   * @covers ::isIdempotent
   */
  public function testOperationSemantics(): void {
    $expected = [
      ToolOperation::Explain->name => [FALSE, TRUE],
      ToolOperation::Read->name => [FALSE, TRUE],
      ToolOperation::Transform->name => [FALSE, TRUE],
      ToolOperation::Trigger->name => [TRUE, FALSE],
      ToolOperation::Write->name => [TRUE, FALSE],
    ];
    foreach (ToolOperation::cases() as $operation) {
      [$modifying, $idempotent] = $expected[$operation->name];
      $this->assertSame($modifying, $operation->isModifying(), $operation->name);
      $this->assertSame($idempotent, $operation->isIdempotent(), $operation->name);
    }
  }

  /**
   * Tests that no operation is both non-modifying and non-idempotent.
   *
   * A non-modifying operation is by definition safe to repeat; consumers
   * deciding confirmation policy rely on this invariant.
   *
   * @covers ::isModifying
   * @covers ::isIdempotent
   */
  public function testNonModifyingImpliesIdempotent(): void {
    foreach (ToolOperation::cases() as $operation) {
      if (!$operation->isModifying()) {
        $this->assertTrue($operation->isIdempotent(), $operation->name);
      }
    }
  }

}
