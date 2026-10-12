<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Exception\RequirementsException;
use Drupal\tool\ExecutableResultInterface;
use Drupal\tool\FailureCategory;
use Drupal\tool\FormattedToolResultInterface;
use Drupal\tool\ToolResultInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ToolBase plugin behavior.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\Tool\ToolBase
 */
#[RunTestsInSeparateProcesses]
class ToolBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * A tool with no checkRequirements() override does not throw.
   *
   * @covers ::checkRequirements
   */
  public function testNoRequirementsToolDoesNotThrow(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    // Should not throw.
    $tool->checkRequirements();
    $this->assertTrue(TRUE);
  }

  /**
   * A tool overriding checkRequirements() throws the expected exception.
   *
   * @covers ::checkRequirements
   */
  public function testUnmetRequirementsToolThrows(): void {
    $tool = $this->toolManager->createInstance('unmet_requirements_tool');
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('Test API key is not configured.');
    $tool->checkRequirements();
  }

  /**
   * Unmet requirements do not block access or execution.
   *
   * The requirements hook is a configuration-time signal for indicators and
   * add-prevention in curation UIs; execute() and access() deliberately do
   * not consult it (#3582975), so tool authors are never tempted to put
   * per-call runtime health checks into it.
   *
   * @covers ::execute
   * @covers ::access
   */
  public function testUnmetRequirementsDoNotBlockExecution(): void {
    $tool = $this->toolManager->createInstance('unmet_requirements_tool');

    $this->assertTrue($tool->access());
    $tool->execute();
    $this->assertTrue($tool->getResult()->isSuccess());
  }

  /**
   * Tests successful tool execution.
   *
   * @covers ::execute
   * @covers ::getResult
   * @covers ::getResultStatus
   * @covers ::getResultMessage
   */
  public function testSuccessfulExecution(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertNotNull($result);
    $this->assertTrue($result->isSuccess());
    $this->assertTrue($tool->getResultStatus());
    $this->assertEquals('Done.', (string) $tool->getResultMessage());
  }

  /**
   * Tests that execute() always sets a result even when doExecute() throws.
   *
   * @covers ::execute
   * @covers ::getResult
   */
  public function testExecutionWithException(): void {
    $tool = $this->toolManager->createInstance('throwing_tool');
    $tool->execute();

    // Result should be set even though an exception was thrown, but the
    // raw exception text must not reach the result: internals (paths,
    // service names, query fragments) would flow to whoever reads it,
    // including LLMs whose output reaches end users (#3582971). Only the
    // exception type is surfaced; the detail goes to the tool logger.
    $result = $tool->getResult();
    $this->assertNotNull($result);
    $this->assertFalse($result->isSuccess());
    $message = (string) $result->getMessage();
    $this->assertStringContainsString('Tool execution failed: RuntimeException', $message);
    $this->assertStringNotContainsString('This tool always throws an exception', $message);

    // An unexpected exception is a Runtime failure, not an Input one: a
    // retry with different arguments cannot fix a bug or an environment
    // failure (#3582993).
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * Tests tool execution that returns a failure result.
   *
   * @covers ::execute
   * @covers ::getResult
   * @covers ::getResultStatus
   */
  public function testFailedExecution(): void {
    $tool = $this->toolManager->createInstance('failing_tool');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertNotNull($result);
    $this->assertFalse($result->isSuccess());
    $this->assertFalse($tool->getResultStatus());
    $this->assertEquals('This tool failed.', (string) $result->getMessage());

    // FailingTool calls ExecutableResult::failure($message) without naming
    // a category, which defaults to Input (#3582993): this keeps the
    // pre-#3582993 behavior for a tool author who never categorizes their
    // own doExecute() failures.
    $this->assertSame(FailureCategory::Input, $result->getFailureCategory());
  }

  /**
   * Tests execution with invalid input throws InvalidArgumentException.
   *
   * @covers ::execute
   * @covers ::getResult
   */
  public function testExecutionWithInvalidInput(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    // Set an invalid string value for a numeric field.
    $tool->setInputValue('number', 'not a number');
    $tool->execute();

    // Result should be set with a failure message.
    $result = $tool->getResult();
    $this->assertNotNull($result);
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('invalid input', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Input, $result->getFailureCategory());
  }

  /**
   * Tests execution with missing required input.
   *
   * @covers ::execute
   * @covers ::getResult
   */
  public function testExecutionWithMissingRequiredInput(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    // Don't set the required input value.
    $tool->execute();

    // Result should be set with a failure message.
    $result = $tool->getResult();
    $this->assertNotNull($result);
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('invalid input', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Input, $result->getFailureCategory());
  }

  /**
   * Tests a success that omits a required output becomes a Runtime failure.
   *
   * The output definitions are the tool's contract with its caller. A tool
   * that declares an output as required and does not set it has a bug, and
   * the caller cannot fix it by changing the arguments, so the failure is
   * Runtime rather than Input.
   *
   * @covers ::execute
   */
  public function testExecutionWithMissingRequiredOutput(): void {
    $tool = $this->toolManager->createInstance('required_output_tool');
    $tool->setInputValue('mode', 'omit');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('The answer output is required but was not provided.', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * Tests a required output set to NULL counts as not provided.
   *
   * @covers ::execute
   */
  public function testExecutionWithNullRequiredOutput(): void {
    $tool = $this->toolManager->createInstance('required_output_tool');
    $tool->setInputValue('mode', 'null');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('The answer output is required but was not provided.', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * Tests a success whose output fails its definition becomes a failure.
   *
   * @covers ::execute
   */
  public function testExecutionWithInvalidOutput(): void {
    $tool = $this->toolManager->createInstance('required_output_tool');
    $tool->setInputValue('mode', 'invalid');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Tool execution failed:', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * Tests an error thrown by output validation becomes a Runtime failure.
   *
   * The exception message can carry internal detail, so the result names
   * only the exception class.
   *
   * @covers ::execute
   */
  public function testExecutionWithThrowingOutputValidation(): void {
    $tool = $this->toolManager->createInstance('required_output_tool');
    $tool->setInputValue('mode', 'url_object');
    $tool->execute();

    $result = $tool->getResult();
    $this->assertFalse($result->isSuccess());
    $this->assertStringContainsString('Output link: The Link output is invalid: TypeError.', (string) $result->getMessage());
    $this->assertStringNotContainsString('parse_url', (string) $result->getMessage());
    $this->assertSame(FailureCategory::Runtime, $result->getFailureCategory());
  }

  /**
   * Tests a success that sets its required output stays a success.
   *
   * ExtraOutputTool covers the other side: an optional output a tool never
   * sets does not fail the execution.
   *
   * @covers ::execute
   */
  public function testExecutionWithCompleteOutputs(): void {
    $tool = $this->toolManager->createInstance('required_output_tool');
    $tool->setInputValue('mode', 'complete');
    $tool->execute();

    $this->assertTrue($tool->getResult()->isSuccess());
    $this->assertSame('forty-two', $tool->getOutputValue('answer'));

    $tool = $this->toolManager->createInstance('extra_output_tool');
    $tool->execute();

    $this->assertTrue($tool->getResult()->isSuccess());
    $this->assertNull($tool->getOutputValue('optional_note'));
  }

  /**
   * Tests access is denied when a required input is missing.
   *
   * @covers ::access
   */
  public function testAccessWithMissingRequiredInput(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');

    $this->assertFalse($tool->access());
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertTrue($access->isForbidden());
    $this->assertInstanceOf(CacheableDependencyInterface::class, $access);
    $this->assertSame(0, $access->getCacheMaxAge());
    $this->assertInstanceOf(AccessResultReasonInterface::class, $access);
    $this->assertSame('Tool input validation failed.', $access->getReason());
  }

  /**
   * Tests access is denied when a required input is invalid.
   *
   * @covers ::access
   */
  public function testAccessWithInvalidRequiredInput(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setInputValue('number', 'not a number');

    $this->assertFalse($tool->access());
    $access = $tool->access(NULL, TRUE);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertTrue($access->isForbidden());
    $this->assertInstanceOf(CacheableDependencyInterface::class, $access);
    $this->assertSame(0, $access->getCacheMaxAge());
    $this->assertInstanceOf(AccessResultReasonInterface::class, $access);
    $this->assertSame('Tool input validation failed.', $access->getReason());
  }

  /**
   * Tests access delegates valid input to the tool plugin.
   *
   * @covers ::access
   */
  public function testAccessWithValidInput(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setInputValue('number', 42);

    $this->assertTrue($tool->access());
    $this->assertTrue($tool->access(NULL, TRUE)->isAllowed());
  }

  /**
   * Tests the execution contract shape after the #3582964 rework.
   *
   * Result values implement the new value interfaces, the deprecated
   * ExecutableResultInterface shell still satisfies instanceof checks for
   * one beta cycle, and the tool is deliberately no longer a core
   * ExecutableInterface (that inheritance carried a scheduled signature
   * change with subject-object semantics that do not fit tools).
   */
  public function testExecutionContractShape(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $tool->execute();

    $this->assertInstanceOf(ToolResultInterface::class, $tool->getResult());
    $this->assertInstanceOf(FormattedToolResultInterface::class, $tool->getFormattedResult());
    $this->assertInstanceOf(ToolResultInterface::class, $tool->getFormattedResult()->getOriginalResult());

    // @phpstan-ignore classConstant.deprecatedInterface (asserting the deliberate BC shell keeps working, #3582964)
    $this->assertInstanceOf(ExecutableResultInterface::class, $tool);
    $this->assertNotInstanceOf(ExecutableInterface::class, $tool);
  }

  /**
   * Tests that getResult() throws an exception when called before execute().
   *
   * @covers ::getResult
   */
  public function testGetResultBeforeExecute(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');

    // Should throw an exception when called before execute().
    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('cannot be retrieved until the action ::execute() method has been called');
    $tool->getResult();
  }

  /**
   * Tests successful execution with valid numeric input and output values.
   *
   * @covers ::execute
   * @covers ::getResult
   * @covers ::getOutputValue
   */
  public function testExecutionWithValidInput(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setInputValue('number', 42);
    $tool->execute();

    $result = $tool->getResult();
    $this->assertNotNull($result);
    $this->assertTrue($result->isSuccess());
    $this->assertStringContainsString('42', (string) $result->getMessage());

    // Test that output context values are set correctly.
    $this->assertEquals(84, $tool->getOutputValue('doubled'));
  }

  /**
   * Tests hasResult() before and after execution.
   *
   * @covers ::hasResult
   */
  public function testHasResult(): void {
    $tool = $this->toolManager->createInstance('no_requirements_tool');
    $this->assertFalse($tool->hasResult());

    $tool->execute();
    $this->assertTrue($tool->hasResult());

    // Failed executions also produce a result.
    $failing = $this->toolManager->createInstance('failing_tool');
    $this->assertFalse($failing->hasResult());
    $failing->execute();
    $this->assertTrue($failing->hasResult());
  }

  /**
   * Tests that the formatted result is memoized per execution.
   *
   * Repeated getters must not re-run the output transform event pipeline,
   * but a fresh execution must produce a fresh formatted result.
   *
   * @covers ::getFormattedResult
   * @covers ::execute
   */
  public function testFormattedResultMemoized(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $tool->setInputValue('name', 'World');
    $tool->execute();

    $first = $tool->getFormattedResult();
    $this->assertSame($first, $tool->getFormattedResult());

    // Re-executing resets the memoized formatted result.
    $tool->execute();
    $this->assertNotSame($first, $tool->getFormattedResult());
  }

  /**
   * Tests result context values without a declared output definition.
   *
   * Regression test: getFormattedResult() previously constructed the output
   * transform event with a NULL definition for undeclared keys, causing a
   * TypeError. Undeclared values must pass through untransformed, mirroring
   * the isset() filter in execute().
   *
   * @covers ::getFormattedResult
   * @covers ::execute
   */
  public function testUndeclaredResultContextValuePassesThrough(): void {
    $tool = $this->toolManager->createInstance('extra_output_tool');
    $tool->execute();

    $this->assertTrue($tool->getResult()->isSuccess());
    // Only declared outputs become output values.
    $this->assertSame('declared value', $tool->getOutputValue('declared'));

    // The formatted result carries both, without a fatal error on the
    // undeclared key.
    $formatted = $tool->getFormattedResult();
    $this->assertSame('declared value', $formatted->getContextValues()['declared']);
    $this->assertSame('undeclared value', $formatted->getContextValues()['extra_debug']);
  }

  /**
   * Tests that a skipped optional output reads as NULL.
   *
   * Regression test for a successful execution turning into a crash at read
   * time: getOutputValues() previously threw ContextException for any
   * declared output the tool did not set, even optional ones, disagreeing
   * with getOutputs() which tolerates the same state.
   *
   * @covers ::getOutputValue
   * @covers ::getOutputValues
   */
  public function testOptionalOutputSkippedByToolReadsAsNull(): void {
    $tool = $this->toolManager->createInstance('extra_output_tool');
    $tool->execute();
    $this->assertTrue($tool->getResult()->isSuccess());

    // Reading the skipped optional output individually returns NULL.
    $this->assertNull($tool->getOutputValue('optional_note'));

    // Reading all values no longer crashes on the skipped optional output.
    $this->assertSame([
      'declared' => 'declared value',
      'optional_note' => NULL,
    ], $tool->getOutputValues());
  }

  /**
   * Tests that a required output that was never set still throws.
   *
   * @covers ::getOutputValue
   */
  public function testRequiredUnsetOutputStillThrows(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    // The 'message' output is required and the tool has not executed.
    $this->expectException(ContextException::class);
    $tool->getOutputValue('message');
  }

  /**
   * Tests that reading an undeclared output still throws.
   *
   * @covers ::getOutputValue
   */
  public function testUndeclaredOutputNameThrows(): void {
    $tool = $this->toolManager->createInstance('greeting_tool');
    $this->expectException(ContextException::class);
    $this->expectExceptionMessage("does not define a 'nonexistent' output");
    $tool->getOutputValue('nonexistent');
  }

}
