<?php

declare(strict_types=1);

namespace Drupal\tool_ai_connector;

use Drupal\tool\Tool\Invoker;
use Drupal\tool\Tool\InvokerCapability;

/**
 * Defines the invoker identity for the AI connector module.
 *
 * The ID is shared between the function call plugin (ToolPluginBase) and
 * any subscriber that gates on AI connector invocations. The factory is the
 * single place that declares what the connector needs from Tool API.
 */
final class ToolAiConnectorInvoker {

  /**
   * The invoker ID used to identify AI connector tool executions.
   */
  public const ID = 'tool_ai_connector';

  /**
   * Builds the invoker for AI connector tool executions.
   *
   * Entities cannot cross a function call boundary, so they travel as
   * handle strings.
   */
  public static function create(): Invoker {
    return new Invoker(self::ID, InvokerCapability::EntitiesAsHandles);
  }

}
