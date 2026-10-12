---
name: use-drupal-tools
description: >-
  Use for any drush tool:* call. Discover, inspect, and run Drupal Tool API
  operations (drupal/tool) with drush tool:search, tool:list, tool:info, and
  tool:run. Load it before the first call, when a tool:run call fails or returns
  less than expected, and when you look up a tool id to call over MCP
  (mcp_server_tool_bridge) or AI function calling (tool_ai_connector).
---

# Use Drupal Tool operations

A Tool operation is a plugin with a declared input and output contract. The
contract is introspectable as JSON Schema. Read it before you call the tool;
never infer input names or values from an example in a README or another skill.

Every command below runs through Drush. Prefix with `ddev`, `lando`, or
`vendor/bin/` as the project requires. Every command supports `--format=json`
for discovery and inspection, and `--json` for execution. Use those; the table
and markdown formats are for humans.

## 1. Find the operation

```bash
drush tool:search "canvas page" --format=json                   # keywords OR-ed over id, label, description
drush tool:search "canvas page" --match=and --operation=write --format=json
drush tool:list --operation=read --format=json                  # everything of one kind
drush tool:list --unmet=only                                    # what still needs configuring
```

`--format=json` prints one list, or `[]` when nothing matches. Each entry:

```json
{"id": "canvas_add_component", "label": "Add component", "description": "…",
 "operation": "write", "destructive": false, "provider": "canvas_tools",
 "requirements": {"met": true, "message": null}}
```

Filter by `operation` before reading descriptions:

| Operation | Modifies state | Use it for |
| --- | --- | --- |
| `explain` | no | schemas, capabilities, "what can I build with" |
| `read` | no | current state, lookups |
| `transform` | no | derive a value; nothing persists |
| `write` | yes | create, update, delete |
| `trigger` | yes | side effects that are not entity writes (send, publish) |

Prefer an `explain` or `read` tool to answer a question. Reach for `write` and
`trigger` only when the task is to change something.

Check `requirements.met` before you pick a tool. `false` means the site is not
configured for it (a missing API key, a disabled module) and `message` says
what is missing. Running it will not help; report the message instead. Pass
`--unmet=hide` to leave those tools out of the listing.

## 2. Inspect before you run

```bash
drush tool:info canvas_add_component --format=json
```

The JSON carries:

- `input_schema`: a JSON Schema object. Build your `--input` from its
  `properties` and `required`. A property can carry `enum`, `minimum` /
  `maximum`, `format`, `pattern`, `const`, `examples`, nested `properties` for
  a map, and `items` for a list. When `enum` is present, those are the only
  valid values. When `examples` is present, copy its shape.
- `inputs`: one entry per input with `type`, `required`, and `locked`. Use it
  to spot locked inputs, which the schema also lists. A list input reports
  `type: list`; do not rely on its `multiple` flag, which stays false.
- `output_schema` and `outputs`: what a successful run returns.
- `operation`, `destructive`, `requirements`: as in the listing.
- `permission`: the permission the tool declares, or `null`. Run as an account
  that holds it: `,` joins permissions that are all required, `+` ones of which
  any is enough. The two cannot be combined in one string. Holding it is not
  always sufficient; the tool can still deny.

Rules that follow from the contract:

- Pass every `required` input. Nothing fills them in for you.
- Skip `locked` inputs. They always take the default from the definition;
  passing one fails with `The <name> input is locked and cannot be changed.`
- A `type: list` input takes a JSON list. Build each element from the
  schema's `items`.
- `destructive: true` means the change is hard to undo. Confirm intent first.

When a string input is an entity id or a machine name and the schema gives no
`enum` or `examples`, a sibling `explain` or `read` tool from the same module
lists what is valid. Search for it before you guess.

An input whose `type` is `entity` or starts with `entity:` cannot be passed from
the command line (#3582977). An id, a map, or a string fails with
`The <name> input must be an entity.` Use a tool that takes the entity type and
id as scalars instead, such as Tool Belt's `tool_belt:entity_update`, which
takes `entity_type_id` and `entity_id`. Search for one before you fall back to
core Drush commands.

`--format=markdown` prints the same data as tables, with a `Constraints`
column and a generated usage line. Use it to read; use `json` to act on.

## 3. Run it

```bash
drush tool:run canvas_add_component --uid=7 \
  --input='{"target_type":"page","target_id":"22","component_id":"sdc.mytheme.card"}' \
  --json
```

`--input` accepts one JSON object or repeated `key=value` pairs. Mixing is
fine; later values win. A `key=value` value that parses as JSON is decoded, so
`--input=limit=5` sets an integer, `--input=published=true` a boolean, and
`--input='props={"title":"Hi"}'` a map. Anything else is a string. An
`--input` that is neither a JSON object nor `key=value` exits `1` with
`Invalid input …`; nothing is silently dropped.

`--json` returns one line:

```json
{"success":true,"message":"Added component.","outputs":{"uuid":"…"}}
```

Branch on `success`. Outputs are populated only when it is `true`. When the
tool runs and fails, `message` holds the reason and `outputs` is empty. When
the command stops before running the tool, the document has `error` and
`error_type` instead of `message` and `outputs`:

```json
{"success":false,"error":"Access denied for tool 'canvas_publish_auto_saves': …","error_type":"access_denied"}
```

Branch on `error_type`:

| `error_type` | Cause | What to do |
| --- | --- | --- |
| `tool_not_found` | No tool has that id. | Search again. |
| `invalid_option` | `--uid` or `--input` did not parse. | Fix the option syntax. |
| `user_not_found`, `user_blocked` | The `--uid` account is missing or blocked. | Pick another account. |
| `invalid_input` | An input was rejected or failed validation. `error` names the input. | Fix the input against `input_schema`. |
| `access_denied` | The account may not run the tool with these inputs. | If you ran without `--uid`, rerun as the task's account. Otherwise report the reason. |
| `execution_failed` | The tool's `execute()` threw. A bug in the tool, not the call. | Report it; do not retry. |

A failed result is not an error: it has `message` and no `error_type`.

Without `--json` the same data prints as `Success:` / `Failed:` lines followed
by an `Outputs:` block.

Exit codes are reliable: `0` for a successful result, `1` for an unknown tool,
invalid input or user, denied access, an execution error, or a failed result.
`tool:info` exits `1` for an unknown tool. A listing with no matches exits `0`.

Chain outputs into the next call. A `write` tool that creates something returns
its id or uuid; that value is the input to the next step, so capture it from the
JSON instead of re-reading state.

## 4. Run as the right user

`--uid` defaults to `0`, anonymous. Pass the account the task is on behalf of on
every call, including `read` tools:

```bash
drush tool:run canvas_publish_auto_saves --input='{"target_id":"22"}' --uid=7
```

Without `--uid`, most `write` and `trigger` tools fail with `access_denied` and
exit `1`. A `read` tool usually succeeds instead and returns only what anonymous
may see. When its message says items were hidden by access, rerun with `--uid`
before you report an empty or short result.

The inputs are validated before access is checked, so `access_denied` is a
permission problem, not a bad input. The message carries the tool's reason
when it gives one. When it does not, read the tool's `checkAccess()` or the
module's permissions.

A denial for the right account is the answer. Report it. Do not grant a role or
permission, unblock or switch the account, or fall back to core Drush, SQL, or
`php:eval` to get past it. Do not switch to `--uid=1` either; it hides the
permission problems the real caller will hit over MCP or in the UI.

## Same operation, other invokers

The plugin id from `tool:list` is the operation's name everywhere:

- **MCP** via `mcp_server_tool_bridge`: each enabled `mcp_tool_config` entity
  exposes one Tool operation as an MCP tool with the same id and the same input
  names. If a tool is missing from the MCP tool list, check for that config
  entity before debugging the tool.
- **AI function calling** via `tool_ai_connector`: same id, same inputs.

Discover and inspect with Drush even when you will call over MCP. The Drush
output is the fuller view of the contract.

## Gotchas

- `tool:run --json` prints the raw PHP output values, not the published ones the
  MCP bridge sends (#3583035). A timestamp prints as an integer where
  `output_schema` says `date-time`. Chain ids and scalars; do not assume an
  output's shape matches its schema until that lands.
- An entity output prints as its full field array under `--json`, including a
  user's password hash, and as a class name without it (#3583047). Do not copy
  an entity output into a report. Read the id from another output or run a
  `read` tool for the fields you need.
- `requirements.met` is a configuration-time signal. Execution never checks it,
  so a tool with unmet requirements still runs and fails on its own terms.
- A tool id can carry a provider prefix, e.g. `tool_belt:entity_list`. Pass the
  whole string; the colon is part of the id.
- A `write` tool's effect may be staged, not live. Read the tool description for
  the publish or save step that makes it visible, and run that tool last.
