<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\tool\ExecutableResult;
use Drupal\tool\FailureCategory;

/**
 * Tests the ExecutableResult value object.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\ExecutableResult
 */
class ExecutableResultTest extends UnitTestCase {

  /**
   * Tests NULL context values normalize to an empty array (#3582962).
   *
   * The getter always returns an array so consumers can iterate without
   * null checks; a tool without outputs reports [].
   *
   * @covers ::__construct
   * @covers ::getContextValues
   */
  public function testNullContextValuesNormalizeToEmptyArray(): void {
    $message = new TranslatableMarkup('Done.');

    $this->assertSame([], (new ExecutableResult(TRUE, $message, NULL))->getContextValues());
    $this->assertSame([], ExecutableResult::success($message, NULL)->getContextValues());
    $this->assertSame([], ExecutableResult::failure($message, NULL)->getContextValues());
    $this->assertSame([], ExecutableResult::success($message)->getContextValues());
  }

  /**
   * Tests provided context values pass through unchanged.
   *
   * @covers ::getContextValues
   */
  public function testContextValuesPassThrough(): void {
    $result = ExecutableResult::success(new TranslatableMarkup('Done.'), ['a' => 1]);
    $this->assertSame(['a' => 1], $result->getContextValues());
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Tests a successful result never reports a failure category.
   *
   * @covers ::getFailureCategory
   */
  public function testSuccessHasNoFailureCategory(): void {
    $result = ExecutableResult::success(new TranslatableMarkup('Done.'));
    $this->assertNull($result->getFailureCategory());
  }

  /**
   * Tests failure() defaults to the Input category.
   *
   * A tool that calls ExecutableResult::failure($message) directly, without
   * naming a category, keeps the behavior it had before #3582993: consumers
   * such as ToolPluginBase::getStructuredOutput() still attach the input
   * schema for it.
   *
   * @covers ::failure
   * @covers ::getFailureCategory
   */
  public function testFailureDefaultsToInputCategory(): void {
    $result = ExecutableResult::failure(new TranslatableMarkup('Failed.'));
    $this->assertSame(FailureCategory::Input, $result->getFailureCategory());
  }

  /**
   * Tests an explicit failure category comes back unchanged.
   *
   * @covers ::failure
   * @covers ::getFailureCategory
   */
  public function testFailureReportsExplicitCategory(): void {
    $result = ExecutableResult::failure(new TranslatableMarkup('Denied.'), NULL, FailureCategory::Access);
    $this->assertSame(FailureCategory::Access, $result->getFailureCategory());

    $result = ExecutableResult::failure(new TranslatableMarkup('Broke.'), NULL, FailureCategory::Runtime);
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

}
