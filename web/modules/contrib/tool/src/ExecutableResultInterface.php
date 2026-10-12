<?php

namespace Drupal\tool;

/**
 * Empty backwards-compatibility shell.
 *
 * This interface was misnamed: it described "a plugin that carries an
 * execution result", not the result value itself. Its methods now live on
 * \Drupal\tool\Tool\ToolInterface, and it no longer extends core's
 * \Drupal\Core\Executable\ExecutableInterface — that inheritance carried a
 * scheduled signature change (execute(?object $object)) with semantics that
 * do not fit tools, and nothing relied on it. ToolInterface still extends
 * this shell so instanceof checks keep working for one beta cycle.
 *
 * @deprecated in tool:1.0.0-beta4 and is removed from tool:1.0.0-rc1. Type
 *   against \Drupal\tool\Tool\ToolInterface instead, and against
 *   \Drupal\tool\ToolResultInterface for result values.
 *
 * @see https://www.drupal.org/project/tool/issues/3582964
 */
interface ExecutableResultInterface {

}
