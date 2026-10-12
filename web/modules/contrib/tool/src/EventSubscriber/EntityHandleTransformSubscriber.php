<?php

declare(strict_types=1);

namespace Drupal\tool\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tool\Event\ToolInputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\Handle\EntityHandleTransformer;
use Drupal\tool\Tool\InvokerCapability;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListOutputDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Swaps content entities for handles when the invoker asks for it.
 *
 * A connector opts in by passing an Invoker carrying
 * InvokerCapability::EntitiesAsHandles to ToolManager::createInstance(). Every
 * handler checks that capability, so a tool
 * called from PHP, or from an invoker that can carry entity objects, is left
 * alone.
 *
 * Input lists are not resolved here. RecursiveToolValueTransformSubscriber
 * re-dispatches the input event once per item with a single-value definition,
 * and onInputTransform() resolves each of those. That also covers
 * ListInputDefinition inputs, whose own data type is "list" and never
 * matches the entity check.
 *
 * Output lists are transformed in one pass so the hint is a single summarized
 * message rather than one per item. The recursive subscriber still walks the
 * transformed list, but EntityHandleTransformer::transformOutput() ignores
 * handle strings, so that walk adds no values and no hints.
 *
 * Handlers run at priority 100 and never stop propagation. Stopping would
 * block InputTypeCoercionSubscriber at 50, which is what wraps a bare handle
 * into a list for multiple inputs, and any third-party subscriber below 100.
 */
final class EntityHandleTransformSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an EntityHandleTransformSubscriber object.
   *
   * @param \Drupal\tool\Handle\EntityHandleTransformer $entityHandleTransformer
   *   The entity handle transformer.
   */
  public function __construct(
    protected readonly EntityHandleTransformer $entityHandleTransformer,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ToolInputTransformEvent::class => ['onInputTransform', 100],
      ToolOutputTransformEvent::class => ['onOutputTransform', 100],
      ToolInputDefinitionNormalizeEvent::class => ['onInputDefinitionNormalize', 100],
      ToolOutputDefinitionNormalizeEvent::class => ['onOutputDefinitionNormalize', 100],
    ];
  }

  /**
   * Resolves a handle input to its entity.
   *
   * Only entity-typed inputs are resolved. A plain string input may
   * legitimately start with the handle prefix and must pass through
   * untouched, and resolveInput() throws on an unknown handle.
   *
   * @param \Drupal\tool\Event\ToolInputTransformEvent $event
   *   The input transform event.
   */
  public function onInputTransform(ToolInputTransformEvent $event): void {
    if (!$event->getInvoker()?->has(InvokerCapability::EntitiesAsHandles)) {
      return;
    }

    if ($this->entityHandleTransformer->isContentEntityDefinition($event->getDefinition())) {
      $event->setValue($this->entityHandleTransformer->resolveInput($event->getValue()));
    }
  }

  /**
   * Transforms entity outputs to handles.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $event
   *   The output transform event.
   */
  public function onOutputTransform(ToolOutputTransformEvent $event): void {
    if (!$event->getInvoker()?->has(InvokerCapability::EntitiesAsHandles)) {
      return;
    }

    $value = $event->getValue();
    $output_name = $event->getOutputName();

    if ($this->isEntityListDefinition($event->getDefinition()) && \is_array($value)) {
      $this->transformBatchOutput($event, $output_name, $value);
      return;
    }

    $this->transformSingleOutput($event, $output_name, $value);
  }

  /**
   * Describes entity inputs as handle strings in the normalized schema.
   *
   * @param \Drupal\tool\Event\ToolInputDefinitionNormalizeEvent $event
   *   The input definition normalize event.
   */
  public function onInputDefinitionNormalize(ToolInputDefinitionNormalizeEvent $event): void {
    if (!$event->getInvoker()?->has(InvokerCapability::EntitiesAsHandles)) {
      return;
    }

    $definition = $event->getDefinition();
    if (!$definition instanceof InputDefinitionInterface) {
      return;
    }

    $transformed = $this->entityHandleTransformer->transformInputDefinition($definition);
    if ($transformed !== NULL) {
      $event->setDefinition($transformed);
    }
  }

  /**
   * Describes entity outputs as handle strings in the normalized schema.
   *
   * @param \Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent $event
   *   The output definition normalize event.
   */
  public function onOutputDefinitionNormalize(ToolOutputDefinitionNormalizeEvent $event): void {
    if (!$event->getInvoker()?->has(InvokerCapability::EntitiesAsHandles)) {
      return;
    }

    $transformed = $this->entityHandleTransformer->transformOutputDefinition($event->getDefinition());
    if ($transformed !== NULL) {
      $event->setDefinition($transformed);
    }
  }

  /**
   * Transforms a single entity output to a handle with its own hint.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $event
   *   The output transform event.
   * @param string $output_name
   *   The output key name.
   * @param mixed $value
   *   The output value.
   */
  protected function transformSingleOutput(ToolOutputTransformEvent $event, string $output_name, mixed $value): void {
    $result = $this->entityHandleTransformer->transformOutput($output_name, $value);
    if ($result === NULL) {
      return;
    }
    $event->setValue($result->value);
    if ($result->hint !== NULL) {
      $event->addMessage($result->hint);
    }
  }

  /**
   * Transforms a list of entity outputs to handles with one batch hint.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $event
   *   The output transform event.
   * @param string $output_name
   *   The output key name.
   * @param array $value
   *   The array of output values.
   */
  protected function transformBatchOutput(ToolOutputTransformEvent $event, string $output_name, array $value): void {
    $entries = [];
    foreach ($value as $delta => $item) {
      if (!$item instanceof ContentEntityInterface) {
        continue;
      }
      $result = $this->entityHandleTransformer->transformOutput($output_name, $item, FALSE);
      if ($result !== NULL) {
        $value[$delta] = $result->value;
        $entries[] = [
          'handle' => $result->value,
          'entity' => $item,
        ];
      }
    }

    $event->setValue($value);

    if ($entries !== []) {
      $event->addMessage($this->buildBatchHint($output_name, $entries));
    }
  }

  /**
   * Determines if a definition describes a list of content entities.
   *
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The output definition.
   *
   * @return bool
   *   TRUE for a multiple entity definition or a ListOutputDefinition whose
   *   item definition is an entity.
   */
  protected function isEntityListDefinition(OutputDefinitionInterface $definition): bool {
    if ($definition->isMultiple() && $this->entityHandleTransformer->isContentEntityDefinition($definition)) {
      return TRUE;
    }

    if ($definition instanceof ListOutputDefinition) {
      return $this->entityHandleTransformer->isContentEntityDefinition($definition->getItemDefinition());
    }

    return FALSE;
  }

  /**
   * Builds a summarized hint for a batch of entity handles.
   *
   * Carries the per-entity identity lines of the single-entity hint without
   * repeating the instruction per item.
   *
   * @param string $output_name
   *   The output key name.
   * @param array $entries
   *   Entries of 'handle' (string) and 'entity' (ContentEntityInterface).
   *
   * @return string
   *   The batch hint message.
   */
  protected function buildBatchHint(string $output_name, array $entries): string {
    $count = \count($entries);
    $lines = [];

    foreach ($entries as $entry) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $entry['entity'];
      $lines[] = \rtrim($this->entityHandleTransformer->buildEntityHintLines($entity, $entry['handle']));
    }

    $hint = "Output `{$output_name}` contains {$count} entity handle(s):\n";
    $hint .= \implode("\n", $lines);
    $hint .= "\nPass these handles to subsequent tool calls that accept entity inputs.";

    return $hint;
  }

}
