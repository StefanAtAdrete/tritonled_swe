<?php

declare(strict_types=1);

namespace Drupal\tool\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\tool\Tool\Invoker;
use Drupal\tool\TypedData\InputDefinitionInterface;

/**
 * Event dispatched during normalization of a tool input definition.
 *
 * This event fires for each input definition node as the normalizer walks the
 * definition tree. Subscribers can replace definitions at any depth based
 * on the invoker and tool context.
 *
 * For example, a subscriber can swap an entity:user definition for a
 * string/email definition when the invoker is an AI agent, because the
 * corresponding ToolInputTransformEvent subscriber will upcast the email
 * to an entity at runtime.
 */
final class ToolInputDefinitionNormalizeEvent extends Event {

  /**
   * Constructs a ToolInputDefinitionNormalizeEvent.
   *
   * @param string $name
   *   The name/key of this definition within its parent context.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The definition being normalized. Core context definitions arrive
   *   from the ai module's override path (#3583018).
   * @param string $toolId
   *   The tool plugin ID.
   * @param \Drupal\tool\Tool\Invoker|null $invoker
   *   The invoker, or NULL if no invoker context is set.
   */
  public function __construct(
    protected readonly string $name,
    protected InputDefinitionInterface|ContextDefinitionInterface $definition,
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
   * @return \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface
   *   The current definition (possibly transformed by a prior subscriber).
   */
  public function getDefinition(): InputDefinitionInterface|ContextDefinitionInterface {
    return $this->definition;
  }

  /**
   * Sets the definition.
   *
   * Subscribers can use this to replace the definition with a version
   * appropriate for the invoker's schema representation.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The new definition.
   *
   * @return $this
   */
  public function setDefinition(InputDefinitionInterface|ContextDefinitionInterface $definition): static {
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
