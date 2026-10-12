<?php

declare(strict_types=1);

namespace Drupal\tool\Exception;

/**
 * Exception thrown when a tool's static requirements are not met.
 *
 * This is thrown by ToolInterface::checkRequirements() when required
 * configuration (e.g. API keys, Drupal config, enabled modules) is absent.
 * It is not intended for runtime failures such as stale credentials or
 * network unavailability.
 *
 * Reaching a configuration or curation UI, this exception's message is the
 * unmet-requirements indicator text; it never blocks execution, because
 * execute() and access() deliberately do not call checkRequirements()
 * (#3582975).
 */
class RequirementsException extends \RuntimeException {}
