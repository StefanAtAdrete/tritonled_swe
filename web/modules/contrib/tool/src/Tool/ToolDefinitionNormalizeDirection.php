<?php

declare(strict_types=1);

namespace Drupal\tool\Tool;

/**
 * Represents the direction of a tool definition during normalization.
 */
enum ToolDefinitionNormalizeDirection: string {

  case Input = 'input';
  case Output = 'output';

}
