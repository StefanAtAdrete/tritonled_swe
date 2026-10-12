<?php

declare(strict_types=1);

namespace Drupal\tool\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\TypedData\OutputDefinitionInterface;

/**
 * Event dispatched during normalization of a tool output definition.
 *
 * This event fires for each output definition node as the normalizer walks the
 * definition tree. Subscribers can replace definitions at any depth based
 * on the invoker and tool context.
 *
 * For example, a subscriber can swap an entity:user definition for a
 * string/token definition when the invoker is an AI agent, because the
 * corresponding ToolOutputTransformEvent subscriber will downcast the entity
 * to a token at runtime.
 */
final class ToolOutputDefinitionNormalizeEvent extends Event {

  /**
   * Constructs a ToolOutputDefinitionNormalizeEvent.
   *
   * @param string $name
   *   The name/key of this definition within its parent context.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The definition being normalized.
   * @param string $toolId
   *   The tool plugin ID.
   * @param \Drupal\tool\Tool\Invoker|null $invoker
   *   The invoker, or NULL if no invoker context is set.
   */
  public function __construct(
    protected readonly string $name,
    protected OutputDefinitionInterface $definition,
    protected readonly string $toolId,
    protected readonly ?Invoker $invoker = NULL,
  ) {
  }

  /**
   * Gets the name/key of this definition.
   *
   * @return string
   *   The name of the definition within its parent context.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Gets the definition being normalized.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The current definition (possibly transformed by a prior subscriber).
   */
  public function getDefinition(): OutputDefinitionInterface {
    return $this->definition;
  }

  /**
   * Sets the definition.
   *
   * Subscribers can use this to replace the definition with a version
   * appropriate for the invoker's schema representation.
   *
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The new definition.
   *
   * @return $this
   */
  public function setDefinition(OutputDefinitionInterface $definition): static {
    $this->definition = $definition;
    return $this;
  }

  /**
   * Gets the tool plugin ID.
   *
   * @return string
   *   The tool plugin ID.
   */
  public function getToolId(): string {
    return $this->toolId;
  }

  /**
   * Gets the invoker identifier.
   *
   * @return \Drupal\tool\Tool\Invoker|null
   *   The invoker, or NULL if no invoker context is set.
   */
  public function getInvoker(): ?Invoker {
    return $this->invoker;
  }

}
