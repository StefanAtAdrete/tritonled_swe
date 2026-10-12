<?php

namespace Drupal\tool;

/**
 * The value contract for an invoker-formatted tool execution result.
 *
 * Implemented by the final FormattedExecutableResult value object, produced
 * by output transforms (e.g. entity-to-handle downcasting) applied to the
 * raw result (#3582964).
 */
interface FormattedToolResultInterface extends ToolResultInterface {

  /**
   * Gets the hints collected during output transformation.
   *
   * @return string[]
   *   Hint lines for the invoker (e.g. handle usage instructions).
   */
  public function getHints(): array;

  /**
   * Gets the raw, untransformed result this was formatted from.
   *
   * @return \Drupal\tool\ToolResultInterface
   *   The original result.
   */
  public function getOriginalResult(): ToolResultInterface;

}
