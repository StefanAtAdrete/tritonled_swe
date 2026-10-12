# Creating a tool

A tool is a plugin. Put the class under `src/Plugin/tool/Tool/` in your module, give
it the `#[Tool]` attribute, and extend `ToolBase`.

## A minimal tool

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\OutputDefinition;

#[Tool(
  id: 'greeting_tool',
  label: new TranslatableMarkup('Greeting Tool'),
  description: new TranslatableMarkup('Generates a greeting message.'),
  operation: ToolOperation::Transform,
  input_definitions: [
    'name' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Name'),
      description: new TranslatableMarkup('The name to greet.'),
    ),
    'greeting' => new InputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Greeting'),
      description: new TranslatableMarkup('The greeting to use.'),
      required: FALSE,
    ),
  ],
  output_definitions: [
    'message' => new OutputDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Message'),
      description: new TranslatableMarkup('The generated greeting message.'),
    ),
  ],
)]
final class GreetingTool extends ToolBase {

  protected function doExecute(array $values): ExecutableResult {
    $greeting = $values['greeting'] ?? 'Hello';
    return ExecutableResult::success(
      new TranslatableMarkup('Greeting generated.'),
      ['message' => "$greeting, {$values['name']}!"],
    );
  }

  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
```

## The attribute

`#[Tool]` accepts:

| Argument | Required | Purpose |
|---|---|---|
| `id` | yes | Plugin ID. Must equal the group or be prefixed with `group:`. For a plain tool, keep the ID and group equal (for example `send_email`). For a derived tool, prefix it (for example `entity:node`). |
| `label` | yes | Human readable name. |
| `description` | yes | What the tool does. Callers and models rely on this. |
| `operation` | yes | A `ToolOperation` case. See below. |
| `destructive` | no | Whether the tool is destructive. Callers use it to prompt for confirmation. |
| `input_definitions` | no | Inputs the tool accepts. See [Input and output definitions](input-output-definitions.md). |
| `input_definition_refiners` | no | Config for dynamically refining inputs. Requires the class to implement `InputDefinitionRefinerInterface`. |
| `output_definitions` | no | Outputs the tool returns. |
| `deriver` | no | A deriver class, to produce many tools from one definition. |
| `forms` | no | Form class overrides keyed by operation. Defaults are provided for `configure` and `execute`. |
| `icon` | no | An icon representing the tool, as a core Icon API full icon ID (`pack_id:icon_id`). See below. |
| `permission` | no | A permission the account must hold, in the syntax of a route's `_permission` requirement. Sufficient for a permission-only tool; a `checkAccess()` override can add restrictions. See [Declared permissions](../configuration.md#declared-permissions). |

### Tool icons

A tool can reference an icon from a [core Icon API](https://www.drupal.org/node/3471494)
icon pack (Drupal 11.1+), declared by any module or theme in a `*.icons.yml` file:

```php
icon: 'my_module:wrench',
```

The definition stores the reference verbatim; nothing resolves or validates it at
discovery time. Consumers such as the Tool Explorer resolve it through the icon pack
manager where available and treat a missing pack, a missing icon, or an older core as
"no icon". Derivers can set the `icon` definition property per derivative, so derived
tools (for example per-entity-type tools) can carry distinct icons.

The module ships a `tool` icon pack with one icon per operation
(`tool:operation-read`, `tool:operation-write`, and so on). The Tool Explorer falls
back to the operation icon for tools that declare no icon of their own, and other
UIs may reuse the pack the same way. The fallback is presentation-only: it never
appears on the definition, so machine consumers see exactly what the tool declared.

## Operations

The `operation` describes the nature of the tool. It drives whether the tool modifies
state and whether it is idempotent, which callers use to decide how to treat it.

| Operation | Modifies state | Idempotent | Use for |
|---|---|---|---|
| `Explain` | no | yes | Returning structure or schema, not data. |
| `Read` | no | yes | Fetching existing data without changing it. |
| `Transform` | no | no | Deriving a result from input, without persisting. |
| `Trigger` | yes | no | Starting a process, queue, cron, or webhook. |
| `Write` | yes | no | Creating, updating, or deleting stored data. |

## Implementing the tool

`ToolBase` requires `doExecute()` and an access rule:

- **`doExecute(array $values): ExecutableResult`** runs the tool. It receives the
  input values keyed by input name and returns a result. Use
  `ExecutableResult::success($message, $outputValues)` on success and
  `ExecutableResult::failure($message)` on failure. Output values are keyed by output
  name and must match your declared `output_definitions`. The base class catches
  exceptions from `doExecute()` and turns them into a failure result, so you do not
  have to wrap everything in try/catch.
- **Access** can be defined with `permission` in the attribute, an override of
  **`checkAccess(array $values, AccountInterface $account, bool $return_as_object): bool|AccessResultInterface`**,
  or both. A permission-only tool can omit `checkAccess()`: the default allows
  access after the declared permission and input validation pass. An override
  decides on its own authority - the declared permission has already been
  required before it runs, so it never needs to call the parent or re-check
  it - and its result is combined with the permission check: both must allow.
  Overriding `access()` itself also counts as an access rule, for a tool
  that replaces the whole gate. A tool with no declared permission and
  neither override is rejected at discovery. The greeting example above
  uses an override to explicitly allow every account.

  The two routes are not equivalent for a rule that does not depend on the
  input values. Invokers check the declared permission before they process
  any caller input (see [Invoking a tool](index.md#invoking-a-tool)), so a
  `permission` denies before transforms resolve handles or refiners run; a
  `checkAccess()` override only runs after the values were resolved and
  validated. Declare a value-independent rule as `permission` and keep
  `checkAccess()` for what genuinely depends on the values.

You can also override **`checkRequirements(): void`** to assert static prerequisites,
such as an API key being configured. Throw a
`Drupal\tool\Exception\RequirementsException` with a message describing what is
missing. This is for static configuration, not runtime state like network
availability.

## Discovery

Tools are discovered by the `plugin.manager.tool` manager from the
`Plugin/tool/Tool` namespace. After adding or changing a tool, rebuild caches:

```bash
drush cr
```

Then confirm it is registered:

```bash
drush tool:info greeting_tool
```
