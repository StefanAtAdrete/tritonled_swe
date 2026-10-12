<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Matrix;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Event\ToolInputTransformEvent;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests how input transform subscribers compose on one dispatch.
 *
 * The pipeline contract: invoker-specific subscribers run at priority 100,
 * generic type coercion at 50, and the recursive child dispatcher at -100.
 * These tests pin that a value transformed early flows through the later
 * stages instead of being stepped on, and that breaking the chain with
 * stopPropagation() visibly disables the later stages.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class TransformSubscriberInteractionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Executes the matrix tool with one input set and returns received values.
   *
   * @param string $name
   *   The input name.
   * @param mixed $value
   *   The value to set.
   *
   * @return array
   *   The values doExecute() received.
   */
  protected function executeWith(string $name, mixed $value): array {
    $tool = $this->container->get('plugin.manager.tool')->createInstance('schema_matrix_tool');
    $tool->setInputValue('required_string', 'present');
    $tool->setInputValue($name, $value);
    $tool->execute();
    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $tool->getResultMessage());
    return $result->getContextValues()['received'];
  }

  /**
   * Registers a transform listener for one input at a priority.
   *
   * @param string $input_name
   *   The input name to react to.
   * @param int $priority
   *   The listener priority.
   * @param callable $callback
   *   The listener body, receiving the event.
   */
  protected function listen(string $input_name, int $priority, callable $callback): void {
    $this->container->get('event_dispatcher')->addListener(
      ToolInputTransformEvent::class,
      function (ToolInputTransformEvent $event) use ($input_name, $callback): void {
        if ($event->getInputName() === $input_name) {
          $callback($event);
        }
      },
      $priority,
    );
  }

  /**
   * Tests that listeners around coercion see the value before and after.
   *
   * A listener between the invoker slot (100) and coercion (50) must see
   * the raw value; a listener between coercion and recursion must see the
   * coerced value. Coercion neither runs early nor overwrites late.
   */
  public function testListenersAroundCoercionSeeBeforeAndAfterValues(): void {
    $seen = [];
    $this->listen('plain_integer', 75, function (ToolInputTransformEvent $event) use (&$seen): void {
      $seen[75] = $event->getValue();
    });
    $this->listen('plain_integer', 25, function (ToolInputTransformEvent $event) use (&$seen): void {
      $seen[25] = $event->getValue();
    });

    $received = $this->executeWith('plain_integer', '5');

    $this->assertSame('5', $seen[75]);
    $this->assertSame(5, $seen[25]);
    $this->assertSame(5, $received['plain_integer']);
  }

  /**
   * Tests that a listener after recursion sees the finished structure.
   *
   * By the time a listener below -100 runs on the top-level event, the
   * bare item has been wrapped (50) and its members coerced through child
   * dispatch (-100): the top-level event carries the finished value.
   */
  public function testListenerAfterRecursionSeesFinishedStructure(): void {
    $seen = NULL;
    $top_events = 0;
    $this->listen('multi_map', -150, function (ToolInputTransformEvent $event) use (&$seen, &$top_events): void {
      // Child dispatches re-enter this listener with the item definition;
      // only the top-level event carries the list definition and the whole
      // list.
      if ($event->getDefinition()->getDataType() === 'list') {
        $seen = $event->getValue();
        $top_events++;
      }
    });

    $received = $this->executeWith('multi_map', ['sku' => 'AB', 'quantity' => '2']);

    $this->assertSame(1, $top_events);
    $this->assertSame([['sku' => 'AB', 'quantity' => 2]], $seen);
    $this->assertSame([['sku' => 'AB', 'quantity' => 2]], $received['multi_map']);
  }

  /**
   * Tests that an invoker-slot transform feeds the rest of the pipeline.
   *
   * A subscriber at priority 100 replacing the value (as handle resolution
   * does) must not disable coercion: its JSON string output is decoded at
   * 50 and the decoded members are coerced through child dispatch at -100.
   */
  public function testInvokerSlotTransformFeedsCoercionAndRecursion(): void {
    $this->listen('typed_map', 100, function (ToolInputTransformEvent $event): void {
      if ($event->getValue() === 'sentinel') {
        $event->setValue('{"title":"from-invoker","limit":"7","verbose":"true"}');
      }
    });

    $received = $this->executeWith('typed_map', 'sentinel');

    $this->assertSame(
      ['title' => 'from-invoker', 'limit' => 7, 'verbose' => TRUE],
      $received['typed_map'],
    );
  }

  /**
   * Tests that stopping propagation in the invoker slot disables coercion.
   *
   * A priority-100 subscriber calling stopPropagation() (as the entity
   * handle subscribers did before EntityHandleTransformSubscriberBase)
   * silences both coercion and recursive child dispatch for that event.
   * The damage is silent, not loud: typed-data primitive validation
   * tolerates a numeric string for an integer, so the tool executes
   * successfully but receives the string '5' where its definition declares
   * an integer. Subscribers stopping propagation own the whole remaining
   * transform for the value.
   */
  public function testStopPropagationInInvokerSlotDisablesCoercion(): void {
    $this->listen('plain_integer', 100, function (ToolInputTransformEvent $event): void {
      $event->stopPropagation();
    });

    $received = $this->executeWith('plain_integer', '5');
    $this->assertSame('5', $received['plain_integer']);
  }

  /**
   * Tests that recursive dispatch carries the item-level definition.
   *
   * Subscribers must trust getDefinition() over looking the definition up
   * by name: the top-level event carries the list definition, and each
   * child dispatch carries the item definition. Without this, item-level
   * coercion would re-apply list rules to every item.
   */
  public function testRecursiveDispatchCarriesItemLevelDefinition(): void {
    $seen = [];
    $this->listen('multi_string', 200, function (ToolInputTransformEvent $event) use (&$seen): void {
      $seen[] = [
        'data_type' => $event->getDefinition()->getDataType(),
        'multiple' => $event->getDefinition()->isMultiple(),
      ];
    });

    $this->executeWith('multi_string', ['one', 'two']);

    $this->assertSame([
      ['data_type' => 'list', 'multiple' => FALSE],
      ['data_type' => 'string', 'multiple' => FALSE],
      ['data_type' => 'string', 'multiple' => FALSE],
    ], $seen);
  }

  /**
   * Tests that sibling inputs are transformed independently.
   *
   * A listener rewriting one input must not leak into another input's
   * events; each setInputValue() dispatch carries its own name, value, and
   * definition.
   */
  public function testSiblingInputsDoNotShareTransforms(): void {
    $this->listen('plain_string', 100, function (ToolInputTransformEvent $event): void {
      $event->setValue('rewritten');
    });

    $tool = $this->container->get('plugin.manager.tool')->createInstance('schema_matrix_tool');
    $tool->setInputValue('required_string', 'present');
    $tool->setInputValue('plain_string', 'original');
    $tool->setInputValue('length_string', 'kept');
    $tool->execute();
    $result = $tool->getResult();
    $this->assertTrue($result->isSuccess(), (string) $tool->getResultMessage());

    $received = $result->getContextValues()['received'];
    $this->assertSame('rewritten', $received['plain_string']);
    $this->assertSame('kept', $received['length_string']);
    $this->assertSame('present', $received['required_string']);
  }

}
