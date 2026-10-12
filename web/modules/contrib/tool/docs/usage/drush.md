# Drush commands

The base Tool module ships four Drush commands for working with tools from the
command line. They need no submodule.

## tool:list

List every registered tool.

```bash
drush tool:list
```

Aliases: `tools`, `tlist`.

Options:

- `--format`: `table` (default), `markdown`, or `json`. Markdown prints full
  descriptions. JSON prints a list of tool summaries, see below.
- `--operation`: filter by operation. One of `explain`, `read`, `transform`,
  `trigger`, `write`.
- `--unmet`: what to do with tools whose requirements are unmet. `show`
  (default) lists them with the reason, `hide` drops them, `only` lists nothing
  else.

Each row carries a `Requirements` column: `Met`, or the message from the tool's
`checkRequirements()`. See [Requirement status](#requirement-status).

```bash
# Only read-only tools, as markdown.
drush tool:list --operation=read --format=markdown

# What still needs configuring before it can run.
drush tool:list --unmet=only
```

The `json` format prints one document: a list with one summary per tool, or
`[]` when nothing matches. Each summary carries the same fields as
`tool:info --format=json` minus the permission and the input and output
definitions and schemas:

```json
[
  {
    "id": "send_email",
    "label": "Send email",
    "description": "Sends an email to the given recipient.",
    "operation": "trigger",
    "destructive": false,
    "provider": "my_module",
    "requirements": {"met": true, "message": null}
  }
]
```

No count or summary line follows the JSON. Take the count from the list length,
and call `tool:info` on an `id` for its schema.

## tool:search

Search tools by keyword against their label, ID, and description.

```bash
drush tool:search "email send"
```

Aliases: `tsearch`.

Options:

- `--match`: `or` (default, match any keyword) or `and` (match all keywords).
- `--format`: `table` (default), `markdown`, or `json`, as above.
- `--operation`: filter by operation, as above.
- `--unmet`: `show` (default), `hide`, or `only`, as above.

Results carry the same `Requirements` column as `tool:list`. Only the tools that
match the keywords are probed.

```bash
drush tool:search "email send" --match=and

# Matching tools that are ready to run.
drush tool:search "email" --unmet=hide
```

## tool:info

Show the full definition of one tool: its operation, flags, declared permission,
requirement status, and input and output definitions.

```bash
drush tool:info send_email
```

Aliases: `tinfo`.

Options:

- `--format`: `table` (default), `markdown`, or `json`.

The `json` format is useful for feeding a tool definition into other tooling.
It carries the tool's JSON Schema as `input_schema` and `output_schema`, its
declared permission as `permission` (`null` when it declares none), and its
requirement status as `requirements`:

```json
"requirements": {"met": false, "message": "Test API key is not configured."}
```

The `table` and `markdown` formats also warn when a declared permission name
matches no defined permission; the JSON output carries the raw string only.

`tool:info` exits `0` whether or not the requirements are met. It describes the
tool; `requirements.met` is the signal for scripts.

## Requirement status

`checkRequirements()` is a configuration-time signal: it tells you whether a tool
is ready to use before you add it to a workflow or agent. `tool:list`,
`tool:search`, and `tool:info` instantiate the tool and call it, then report one
of:

- `Met`: the tool raised no `RequirementsException`.
- The exception message, such as `Test API key is not configured.`
- `Could not instantiate tool: ...` when the plugin's `create()` throws, for
  example because its provider has a broken dependency. One such plugin never
  hides the rest of the listing.

Execution never consults `checkRequirements()`. A tool with unmet requirements
still runs through `tool:run` and fails on its own terms.

## tool:run

Execute a tool with the inputs you provide.

```bash
drush tool:run send_email --uid=2 --input='{"recipient":"test@example.com","subject":"Test","message":"Hello"}'
```

Aliases: `trun`.

Options:

- `--input`: input values, either as a JSON object or as `key=value` pairs. Repeat
  the flag for multiple `key=value` inputs.
- `--json`: print the result as JSON.
- `--uid`: run as the given user ID. Omitting this option or passing `--uid=0`
  runs as anonymous. Pass an explicit ID for an enabled account with the tool's
  required permissions. Use `--uid=1` only for deliberate superuser execution.

The examples use user 2; replace this with an enabled account that has permission
to run the tool. Invalid user IDs, missing accounts, and blocked accounts fail
before the tool executes.

Use decimal user IDs without leading zeros, such as `7` rather than `007`.
An optional sign and surrounding whitespace are accepted, but the resulting
user ID must be non-negative.

Malformed `--input` values fail instead of being ignored. JSON input must be an
object containing named input values, not a scalar or a list. Input names in
either format must be non-empty strings that PHP does not convert to integer keys.
Values can contain nested JSON objects or arrays.

```bash
# key=value inputs, run as user 2.
drush tool:run send_email --uid=2 \
  --input=recipient=test@example.com \
  --input=subject=Test \
  --input=message=Hello
```

Because `tool:run` executes real actions, run it deliberately, especially for tools
whose operation is `write` or `trigger`.

### Errors

The command checks the tool's declared permission first, before any input is
parsed or processed, then validates the inputs, then checks `access()` - and
reports input problems and denials apart. A missing or invalid input names
the input and what is wrong with it. A denial carries the reason the tool
gives, when it gives one:

```text
Input validation failed for tool 'send_email': Input recipient: This value should not be null.
Access denied for tool 'send_email': The 'send emails' permission is required.
```

The order matters twice over. An account without the declared permission is
denied before its input is processed at all, so a permission denial can
precede any input feedback. For an account that holds the permission,
validation runs before `access()`: `access()` fails closed with a generic
reason when the inputs are invalid, so a command that checked it first would
report an argument problem as a permission problem.

With `--json`, a failure before the tool runs is one document with `success`
`false`, the message as `error`, and an `error_type` to branch on:

```json
{
  "success": false,
  "error": "Access denied for tool 'send_email': The 'send emails' permission is required.",
  "error_type": "access_denied"
}
```

The error types are:

- `tool_not_found`: no tool has that ID.
- `invalid_option`: `--uid` or `--input` could not be parsed.
- `user_not_found`: no account has the `--uid` ID.
- `user_blocked`: the `--uid` account is blocked.
- `invalid_input`: the tool rejected an input value, or the inputs failed
  validation.
- `access_denied`: the account lacks the tool's declared permission (reported
  before the inputs are processed), or may not run the tool with these
  inputs.
- `execution_failed`: `execute()` threw. `ToolBase` turns anything
  `doExecute()` throws into a failed result, so with it this only happens when
  the result holds an output value its output definition cannot store: a bug
  in the tool, not in the call.

A tool that runs and reports its own failure is a result, not an error: that
document has `success` `false`, the tool's `message`, and no `error_type`. An
exception thrown inside `doExecute()` is one of these, because `ToolBase`
catches it and reports a failed result.

## Exit codes

Successful commands return exit code `0`. `tool:run` returns `1` for an unknown
tool, invalid input or user, denied access, an execution error, or a failed result.
`tool:info` returns `1` for an unknown tool. `tool:list` and `tool:search` return
`1` for an invalid `--operation` or `--unmet` value, and `tool:search` also
returns `1` when no search keywords are provided. An empty list or a search with
no matches is a successful result (`0`), not an error.
