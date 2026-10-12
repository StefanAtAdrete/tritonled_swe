<?php

declare(strict_types=1);

namespace Drupal\tool\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\InputDefinitionInterface;

/**
 * Event dispatched when an input value is set on a tool.
 *
 * Subscribers can use this event to transform input values based on the
 * invoker identity. For example, upcasting a user email address to a user
 * entity when the invoker is an AI agent.
 *
 * The event carries the effective definition for the value at the current
 * recursion depth. Subscribers should use getDefinition() rather than looking
 * up the definition from the tool by name, because during recursive dispatch
 * the event may represent a single item within a list/multiple definition.
 */
final class ToolInputTransformEvent extends Event {

  /**
   * Constructs a ToolInputTransformEvent.
   *
   * @param string $inputName
   *   The name of the input being set.
   * @param mixed $value
   *   The input value being set.
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance receiving the input.
   * @param \Drupal\tool\Tool\Invoker|null $invoker
   *   The invoker, or NULL if no invoker context is set.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The effective definition for this value at the current recursion
   *   depth.
   */
  public function __construct(
    protected readonly string $inputName,
    protected mixed $value,
    protected readonly ToolInterface $tool,
    protected readonly ?Invoker $invoker,
    protected readonly InputDefinitionInterface $definition,
  ) {
  }

  /**
   * Gets the input name.
   *
   * @return string
   *   The name of the input being set.
   */
  public function getInputName(): string {
    return $this->inputName;
  }

  /**
   * Gets the input value.
   *
   * @return mixed
   *   The current input value (possibly transformed by a prior subscriber).
   */
  public function getValue(): mixed {
    return $this->value;
  }

  /**
   * Sets the input value.
   *
   * Subscribers can use this to replace the value with a transformed version.
   *
   * @param mixed $value
   *   The new value.
   *
   * @return $this
   */
  public function setValue(mixed $value): static {
    $this->value = $value;
    return $this;
  }

  /**
   * Gets the tool instance.
   *
   * @return \Drupal\tool\Tool\ToolInterface
   *   The tool instance.
   */
  public function getTool(): ToolInterface {
    return $this->tool;
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

  /**
   * Gets the effective definition for this value.
   *
   * During recursive dispatch, this reflects the item-level definition (e.g.,
   * non-multiple) rather than the top-level definition. Subscribers should
   * prefer this over looking up the definition from the tool by name.
   *
   * @return \Drupal\tool\TypedData\InputDefinitionInterface
   *   The effective definition.
   */
  public function getDefinition(): InputDefinitionInterface {
    return $this->definition;
  }

}
