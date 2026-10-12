<?php

declare(strict_types=1);

namespace Drupal\tool\Tool;

/**
 * Identifies the context calling a tool and what that context needs.
 *
 * A connector passes an Invoker to ToolManager::createInstance() instead of a
 * bare ID string when it needs Tool API to adapt values for its boundary.
 * Each capability enables one subscriber, so a connector opts in at the call
 * site without registering services of its own.
 */
final class Invoker implements \Stringable {

  /**
   * The capabilities this invoker asked for.
   *
   * @var \UnitEnum[]
   */
  private readonly array $capabilities;

  /**
   * Constructs an Invoker.
   *
   * @param string $id
   *   The invoker identifier, for example "mcp_server".
   * @param \UnitEnum ...$capabilities
   *   Behaviors to apply at this boundary, such as
   *   InvokerCapability::EntitiesAsHandles. Any enum case is accepted so other
   *   modules can define capabilities of their own.
   */
  public function __construct(
    public readonly string $id,
    \UnitEnum ...$capabilities,
  ) {
    $this->capabilities = $capabilities;
  }

  /**
   * Builds an invoker from a legacy string ID or passes an Invoker through.
   */
  public static function from(string|self $invoker): self {
    return $invoker instanceof self ? $invoker : new self($invoker);
  }

  /**
   * Checks whether this invoker asked for a capability.
   */
  public function has(\UnitEnum $capability): bool {
    return \in_array($capability, $this->capabilities, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return $this->id;
  }

}
