<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Matrix;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\TypedData\OutputDefinition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Matrix tests for output values through the output transform pipeline.
 *
 * The mirror of InputCoercionMatrixTest for outputs: each case dispatches
 * the output transform event getFormattedResult() dispatches for one value,
 * and asserts the exact value invokers receive.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class OutputCoercionMatrixTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Provides output values and the exact value invokers must receive.
   *
   * @return \Generator
   *   Cases of [output data type, PHP value, expected formatted value].
   */
  public static function formattedProvider(): \Generator {
    // Decimals travel as strings rounded to 15 significant digits, so a
    // float with an integer part comes back without binary rounding error
    // (#3583067).
    yield 'decimal from float' => ['decimal', 19.99, '19.99'];
    yield 'decimal from integer' => ['decimal', 5, '5'];
    yield 'decimal from small float avoids exponent form' => ['decimal', 0.0000001, '0.0000001'];
    yield 'decimal from float with an integer part' => ['decimal', 1234.567, '1234.567'];
    yield 'decimal from float with five integer digits' => ['decimal', 99999.99, '99999.99'];
    yield 'decimal from float with eight integer digits' => ['decimal', 12345678.123, '12345678.123'];
    yield 'decimal from negative float' => ['decimal', -1234.567, '-1234.567'];
    yield 'decimal from float artifact collapses' => ['decimal', 0.1 + 0.2, '0.3'];
    yield 'decimal from zero' => ['decimal', 0.0, '0'];
    yield 'decimal string passes through' => ['decimal', '7.25', '7.25'];
  }

  /**
   * Tests that an output value is sent the way its schema describes.
   *
   * @param string $data_type
   *   The output data type.
   * @param mixed $value
   *   The value the tool returned.
   * @param mixed $expected
   *   The value invokers must receive.
   *
   * @dataProvider formattedProvider
   */
  #[DataProvider('formattedProvider')]
  public function testOutputValueIsFormatted(string $data_type, mixed $value, mixed $expected): void {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('timestamp_output_tool');
    $definition = new OutputDefinition($data_type, 'Value', 'The value.');
    $event = new ToolOutputTransformEvent('value', $value, $tool, NULL, $definition);
    $this->container->get('event_dispatcher')->dispatch($event);
    $this->assertSame($expected, $event->getValue());
  }

}
