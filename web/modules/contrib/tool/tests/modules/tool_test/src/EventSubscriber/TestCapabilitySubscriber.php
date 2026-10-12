<?php

declare(strict_types=1);

namespace Drupal\tool_test\EventSubscriber;

use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool_test\TestInvokerCapability;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Upper-cases string outputs for invokers that ask for it.
 *
 * Gated on a capability defined outside Tool API, so it proves a module can
 * ship its own enum and have Invoker::has() honor it.
 */
final class TestCapabilitySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ToolOutputTransformEvent::class => 'onOutputTransform',
    ];
  }

  /**
   * Upper-cases a string output when the invoker carries the capability.
   */
  public function onOutputTransform(ToolOutputTransformEvent $event): void {
    if (!$event->getInvoker()?->has(TestInvokerCapability::UppercaseOutputs)) {
      return;
    }
    $value = $event->getValue();
    if (\is_string($value)) {
      $event->setValue(\strtoupper($value));
    }
  }

}
