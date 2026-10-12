<?php

declare(strict_types=1);

namespace Drupal\tool\EventSubscriber;

use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\tool\TypedData\MapOutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches value transform events for nested values in known definitions.
 */
final class RecursiveToolValueTransformSubscriber implements EventSubscriberInterface {

  /**
   * TRUE while this subscriber is dispatching nested input events.
   */
  protected bool $dispatchingInputChildren = FALSE;

  /**
   * TRUE while this subscriber is dispatching nested output events.
   */
  protected bool $dispatchingOutputChildren = FALSE;

  /**
   * Constructs a RecursiveToolValueTransformSubscriber object.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ToolInputTransformEvent::class => ['onInputTransform', -100],
      ToolOutputTransformEvent::class => ['onOutputTransform', -100],
    ];
  }

  /**
   * Dispatches input transform events for nested input values.
   *
   * @param \Drupal\tool\Event\ToolInputTransformEvent $event
   *   The input transform event.
   */
  public function onInputTransform(ToolInputTransformEvent $event): void {
    if ($this->dispatchingInputChildren) {
      return;
    }

    $tool = $event->getTool();
    $name = $event->getInputName();
    $definition = $event->getDefinition();

    $event->setValue($this->transformInputChildren(
      $tool,
      $name,
      $definition,
      $event->getValue(),
      $event->getInvoker(),
    ));
  }

  /**
   * Dispatches output transform events for nested output values.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $event
   *   The output transform event.
   */
  public function onOutputTransform(ToolOutputTransformEvent $event): void {
    if ($this->dispatchingOutputChildren) {
      return;
    }

    $definition = $event->getDefinition();
    $name = $event->getOutputName();

    $event->setValue($this->transformOutputChildren(
      $event,
      $name,
      $definition,
      $event->getValue(),
    ));
  }

  /**
   * Walks children of an input value and dispatches transform events for them.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool receiving the input value.
   * @param string $name
   *   The current value name.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The definition describing the current value.
   * @param mixed $value
   *   The current value.
   * @param \Drupal\tool\Tool\Invoker|null $invoker
   *   The invoker, or NULL if no invoker is set.
   *
   * @return mixed
   *   The transformed value.
   */
  protected function transformInputChildren(ToolInterface $tool, string $name, InputDefinitionInterface $definition, mixed $value, ?Invoker $invoker): mixed {
    if (!is_array($value)) {
      return $value;
    }

    if ($definition->isMultiple()) {
      $item_definition = clone $definition;
      $item_definition->setMultiple(FALSE);
      foreach ($value as $delta => $item) {
        $value[$delta] = $this->dispatchInputValue($tool, $name, $item_definition, $item, $invoker);
      }
      return $value;
    }

    if ($definition instanceof ListDataDefinition) {
      $item_definition = $definition->getItemDefinition();
      foreach ($value as $delta => $item) {
        $value[$delta] = $this->dispatchInputValue($tool, $name, $item_definition, $item, $invoker);
      }
      return $value;
    }

    if ($definition instanceof MapDataDefinition) {
      foreach ($definition->getPropertyDefinitions() as $property_name => $property_definition) {
        if (array_key_exists($property_name, $value)) {
          $value[$property_name] = $this->dispatchInputValue($tool, (string) $property_name, $property_definition, $value[$property_name], $invoker);
        }
      }
    }

    return $value;
  }

  /**
   * Dispatches an input transform event and walks the transformed child value.
   *
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool receiving the input value.
   * @param string $name
   *   The current value name.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The definition describing the current value.
   * @param mixed $value
   *   The current value.
   * @param \Drupal\tool\Tool\Invoker|null $invoker
   *   The invoker, or NULL if no invoker is set.
   *
   * @return mixed
   *   The transformed value.
   */
  protected function dispatchInputValue(ToolInterface $tool, string $name, InputDefinitionInterface $definition, mixed $value, ?Invoker $invoker): mixed {
    $this->dispatchingInputChildren = TRUE;
    try {
      $event = new ToolInputTransformEvent($name, $value, $tool, $invoker, $definition);
      $this->eventDispatcher->dispatch($event);
      $value = $event->getValue();
    }
    finally {
      $this->dispatchingInputChildren = FALSE;
    }

    return $this->transformInputChildren($tool, $name, $definition, $value, $invoker);
  }

  /**
   * Walks children of an output value and dispatches transform events for them.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $parent_event
   *   The parent output transform event.
   * @param string $name
   *   The current value name.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The definition describing the current value.
   * @param mixed $value
   *   The current value.
   *
   * @return mixed
   *   The transformed value.
   */
  protected function transformOutputChildren(ToolOutputTransformEvent $parent_event, string $name, OutputDefinitionInterface $definition, mixed $value): mixed {
    if (!is_array($value)) {
      return $value;
    }

    if ($definition->isMultiple()) {
      $item_definition = clone $definition;
      $item_definition->setMultiple(FALSE);
      foreach ($value as $delta => $item) {
        $value[$delta] = $this->dispatchOutputValue($parent_event, $name, $item_definition, $item);
      }
      return $value;
    }

    if ($definition instanceof ListOutputDefinition) {
      $item_definition = $definition->getItemDefinition();
      foreach ($value as $delta => $item) {
        $value[$delta] = $this->dispatchOutputValue($parent_event, $name, $item_definition, $item);
      }
      return $value;
    }

    if ($definition instanceof MapOutputDefinition) {
      foreach ($definition->getPropertyDefinitions() as $property_name => $property_definition) {
        if (array_key_exists($property_name, $value)) {
          $value[$property_name] = $this->dispatchOutputValue($parent_event, (string) $property_name, $property_definition, $value[$property_name]);
        }
      }
    }

    return $value;
  }

  /**
   * Dispatches an output transform event and walks the transformed child value.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $parent_event
   *   The parent output transform event.
   * @param string $name
   *   The current value name.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The definition describing the current value.
   * @param mixed $value
   *   The current value.
   *
   * @return mixed
   *   The transformed value.
   */
  protected function dispatchOutputValue(ToolOutputTransformEvent $parent_event, string $name, OutputDefinitionInterface $definition, mixed $value): mixed {
    $this->dispatchingOutputChildren = TRUE;
    try {
      $event = new ToolOutputTransformEvent($name, $value, $parent_event->getTool(), $parent_event->getInvoker(), $definition);
      $this->eventDispatcher->dispatch($event);
      $value = $event->getValue();
    }
    finally {
      $this->dispatchingOutputChildren = FALSE;
    }

    foreach ($event->getMessages() as $message) {
      $parent_event->addMessage($message);
    }

    return $this->transformOutputChildren($parent_event, $name, $definition, $value);
  }

}
