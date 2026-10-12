<?php

declare(strict_types=1);

namespace Drupal\tool_ai_connector\Context;

use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * An entity-aware core context definition adapted from a tool definition.
 *
 * Created through ToolContextDefinition::fromToolDefinition(), which routes
 * entity data types here because core's ContextDefinition constructor
 * rejects them.
 *
 * @see \Drupal\tool_ai_connector\Context\ToolContextDefinition
 */
final class ToolEntityContextDefinition extends EntityContextDefinition implements ToolContextDefinitionInterface {

  use ToolContextDefinitionTrait;

}
