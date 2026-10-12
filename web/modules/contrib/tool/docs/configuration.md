# Configuration

The Tool module has no global settings form. Tools are plugins discovered from code,
so there is nothing to configure for the base module beyond permissions.

## Permissions

The module defines two permissions:

| Permission | Machine name | Grants |
|---|---|---|
| Administer tools | `administer tool` | Browsing tool definitions and executing tools through the Tool Explorer. |
| View tool information | `view tool information` | Browsing tool definitions in the Tool Explorer, without the execute forms. |

Both permissions are marked restricted. Grant `administer tool` only to trusted
roles: a tool that runs behind the Explorer executes with the permissions of the
acting user, and each tool's own access check still applies, but the Explorer is
an administrative surface that lists every tool on the site. `view tool
information` grants the read-only half - browsing definitions and schemas - for
roles that audit tools without running them.

!!! warning
    Executing a tool can read data, write data, or trigger processes depending on
    the tool's operation. Treat `administer tool` as an administrative permission.

## Access control

Each tool defines access by declaring a permission, overriding `checkAccess()`,
or both. When a tool runs, the runtime calls `access()` against the acting account.
The default `checkAccess()` allows access after the declared permission check and
input validation pass. A tool that declares no permission must override
`checkAccess()`, or `access()` itself, to grant access - a definition with no
access rule at all is rejected when tools are discovered - and an override
never needs to re-check the declared permission, which is required before it
runs.

`access()` answers "may this account run this tool with the values currently set
on it", so it resolves and validates the inputs before it consults
`checkAccess()`. Invalid input, or a required input that is not set, is denied
with the generic reason `Tool input validation failed.`; the violations are not
carried into the access reason.

A denial therefore has two meanings that the result alone does not separate: the
account may not do this, or the arguments are wrong and can be corrected. Call
`validateInputs()` before `access()` and report its violations, otherwise an
argument problem is reported to the user, or to a model, as a permission
problem:

```php
$violations = $tool->validateInputs();
if ($violations->count() > 0) {
  // Correctable: tell the caller what to fix.
}
elseif (!$tool->access($account)) {
  // Not correctable: the account may not run this tool.
}
```

The Drush [`tool:run`](usage/drush.md) command runs as anonymous by default,
including when you pass `--uid=0`. For a tool that requires permissions, pass
`--uid` with the ID of an enabled account that has those permissions. The tool's
access checks and execution use that account. Pass `--uid=1` only when you
deliberately want superuser execution.

### Declared permissions

A tool can also declare a permission in its `#[Tool]` attribute. The string uses
the syntax of a route's `_permission` requirement: a single permission name, the
form an entity type's `admin_permission` takes, or several names joined with `,`
when all of them are required, or with `+` when any one of them is enough.
Spaces around each name are trimmed. The two operators cannot be combined in one
string. `access()` checks the declared permission first, before it validates the
inputs or consults `checkAccess()`.

```php
permission: 'administer users',
```

A tool whose access rule is fully expressed by the declaration can use the default
`checkAccess()` without an override. An override adds another requirement: both
the declared permission and the custom check must allow access. A neutral or
forbidden result from the custom check denies execution even when the permission
is satisfied. A permission that depends on runtime values, such as the entity
type specific `administer node fields`, stays in `checkAccess()`.

A name that matches no defined permission denies every
account except user 1 and accounts with an administrator role, which pass every
permission check. Check a new declaration with
[`drush tool:info`](usage/drush.md), which prints it and flags any name that
matches no defined permission.

Because the permission is part of the definition,
`ToolManager::checkPermission()` checks it against a definition from
`getDefinitions()` without instantiating the tool, so a catalog or picker can
filter tools for an account before any input values exist. A tool with no declared
permission passes this filter for every caller, but still needs a `checkAccess()`
override to grant access at execution time.

## Config schema for tool inputs

A tool that accepts configuration should ship a config schema entry keyed
`tool.plugin.<plugin_id>`. The [Tool Explorer](usage/tool-explorer.md) view page
checks whether this schema exists and, when it is missing, prints a suggested schema
built from the tool's input definitions. Copy that suggestion into
`config/schema/<module>.schema.yml` in the module that provides the tool.

The suggested schema marks the root mapping `FullyValidatable`, which makes Drupal
validate the stored values against their constraints. See
[Input and output definitions](developers/input-output-definitions.md) for how input
definitions map to schema.
