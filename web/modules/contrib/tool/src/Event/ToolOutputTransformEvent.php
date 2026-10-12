<?php

declare(strict_types=1);

namespace Drupal\tool\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\OutputDefinitionInterface;

/**
 * Event dispatched per output value when formatted results are requested.
 *
 * Subscribers can use this event to transform individual output values based
 * on the invoker identity. For example, downcasting a Drupal entity to a
 * token representation for an AI agent.
 *
 * Subscribers can also add messages that provide context about the
 * transformation (e.g., explaining how to use a token in subsequent calls).
 *
 * The event carries the effective definition for the value at the current
 * recursion depth. Subscribers should use getDefinition() rather than looking
 * up the definition from the tool by name, because during recursive dispatch
 * the event may represent a single item within a list/multiple definition.
 */
final class ToolOutputTransformEvent extends Event {

  /**
   * Messages added by subscribers during transformation.
   *
   * @var string[]
   */
  protected array $messages = [];

  /**
   * Constructs a ToolOutputTransformEvent.
   *
   * @param string $outputName
   *   The name of the output being transformed.
   * @param mixed $value
   *   The output value being transformed.
   * @param \Drupal\tool\Tool\ToolInterface $tool
   *   The tool instance that produced the output.
   * @param \Drupal\tool\Tool\Invoker|null $invoker
   *   The invoker, or NULL if no invoker context is set.
   * @param \Drupal\tool\TypedData\OutputDefinitionInterface $definition
   *   The effective definition for this value at the current recursion depth.
   */
  public function __construct(
    protected readonly string $outputName,
    protected mixed $value,
    protected readonly ToolInterface $tool,
    protected readonly ?Invoker $invoker,
    protected readonly OutputDefinitionInterface $definition,
  ) {
  }

  /**
   * Gets the output name.
   *
   * @return string
   *   The name of the output being transformed.
   */
  public function getOutputName(): string {
    return $this->outputName;
  }

  /**
   * Gets the output value.
   *
   * @return mixed
   *   The current output value (possibly transformed by a prior subscriber).
   */
  public function getValue(): mixed {
    return $this->value;
  }

  /**
   * Sets the output value.
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
   * Adds a message providing context about the transformation.
   *
   * Multiple subscribers can add messages. These are aggregated and appended
   * to the formatted result's message.
   *
   * @param string $message
   *   A message explaining the transformation (e.g., "Use token [user:42]
   *   in subsequent calls to reference this user.").
   *
   * @return $this
   */
  public function addMessage(string $message): static {
    $this->messages[] = $message;
    return $this;
  }

  /**
   * Gets all messages added by subscribers.
   *
   * @return string[]
   *   The messages added during transformation.
   */
  public function getMessages(): array {
    return $this->messages;
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
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The effective definition.
   */
  public function getDefinition(): OutputDefinitionInterface {
    return $this->definition;
  }

}
