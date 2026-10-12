<?php

declare(strict_types=1);

namespace Drupal\tool\Drush;

/**
 * Names why tool:run failed before or while running a tool.
 *
 * The value is the error_type field of the command's JSON output, so a
 * script or an agent driving tool:run can branch on it instead of parsing
 * the message. Only failures the command itself reports carry one: a tool
 * that runs and reports its own failure is a result, not an error.
 */
enum RunErrorType: string {

  // No tool has the given plugin ID.
  case ToolNotFound = 'tool_not_found';

  // The --uid or --input option could not be parsed.
  case InvalidOption = 'invalid_option';

  // No account has the --uid ID.
  case UserNotFound = 'user_not_found';

  // The --uid account is blocked.
  case UserBlocked = 'user_blocked';

  // The tool rejected an input value, or the inputs failed validation.
  case InvalidInput = 'invalid_input';

  // The account may not run the tool with these inputs.
  case AccessDenied = 'access_denied';

  // The tool's execute() threw. ToolBase turns anything doExecute() throws
  // into a failed result, so with it this only happens when the result holds
  // an output value its output definition cannot store.
  case ExecutionFailed = 'execution_failed';

}
