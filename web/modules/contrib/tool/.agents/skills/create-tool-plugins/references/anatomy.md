# Tool anatomy — the contract

Everything a Tool author must know about the base class, the attribute, inputs,
outputs, and results. All paths are relative to the `tool` module's own
directory (wherever it is installed, e.g. `web/modules/contrib/tool/`).

## ToolBase — what you implement vs. what you get

`Drupal\tool\Tool\ToolBase` (`src/Tool/ToolBase.php`) is abstract. You implement
exactly two methods; the base class provides the rest.

```php
// You MUST implement these two:
abstract protected function doExecute(array $values): ExecutableResult;
abstract protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface;
```

Provided by `ToolBase` (do not re-implement unless you have a reason):

- `execute(): static` — wraps `doExecute()` in try/catch. Catches
  `\InvalidArgumentException` (→ "invalid input" failure) and `\Throwable` (→
  "execution failed" failure), so a throwing `doExecute()` still yields a
  failure `ExecutableResult` rather than a fatal. On success it copies result
  context values into outputs **only for keys declared in
  `output_definitions`**.
- `access(?AccountInterface $account = NULL, $return_as_object = FALSE)` — public
  entry point. Resolves account to `currentUser` if NULL, fetches the executable
  values, and calls your `checkAccess()`.
- `getResult()`, `getResultStatus()`, `getResultMessage()` — throw
  `\BadMethodCallException` if called before `execute()`.
- `checkRequirements(): void` — static prerequisites (API keys, config).
  Default is a no-op. Override to `throw new RequirementsException(...)` when a
  precondition is missing. This is **not** for per-call validation.

### The constructor is `final`

```php
final public function __construct(
  array $configuration,
  string $plugin_id,
  mixed $plugin_definition,
  protected AccountInterface $currentUser,
);
```

You cannot override it. Inject services by overriding the static factory:

```php
public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
  $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
  $instance->entityTypeManager = $container->get('entity_type.manager');
  return $instance;
}
```

Declare each injected service as a typed protected property. Common ones seen in
`tool_belt`: `entity_type.manager`, `entity_field.manager`,
`entity_type.bundle.info`, `plugin.manager.mail`,
`plugin.manager.field.field_type`, `messenger`, `language_manager`, `token`,
`logger.factory`, and for API tools `http_client`.

## The `#[Tool]` attribute

`Drupal\tool\Attribute\Tool` (`src/Attribute/Tool.php`). Parameters:

| Param | Type | Notes |
|-------|------|-------|
| `id` | `string` | A single token, or `prefix:name`. The `prefix` ("group") is a stable namespace you choose — often a module *family*, not one module: every `tool_belt_*` submodule shares the `tool_belt:` prefix (e.g. `tool_belt:log_message`). The `:` is also Drupal's derivative separator. Pick the wrong shape and the plugin is silently not discovered. |
| `label` | `TranslatableMarkup` | Human name. |
| `description` | `TranslatableMarkup` | One sentence; this is what an agent reads to pick the tool. Make it count. |
| `operation` | `ToolOperation` | Required. See enum below. |
| `destructive` | `bool` = FALSE | TRUE for irreversible actions; encourages confirmation. |
| `input_definitions` | `InputDefinitionInterface[]` | Keyed by input name. |
| `input_definition_refiners` | `array` | `['input' => ['dependency_input', ...]]`. Requires the class to implement `InputDefinitionRefinerInterface` or the attribute throws. See `refiners-and-derivatives.md`. |
| `output_definitions` | `OutputDefinitionInterface[]` | Keyed by output name. |
| `deriver` | `class-string\|null` | A deriver class. See `refiners-and-derivatives.md`. |
| `forms` | `array<string,string\|false>` | Rarely needed; `configure`/`execute` forms are auto-added by the manager. |

## ToolOperation enum

`src/Tool/ToolOperation.php`. Pick the one that matches the tool's nature:

| Case | Meaning | `isModifying()` | `isIdempotent()` |
|------|---------|-----------------|------------------|
| `Explain` | Structural/contextual info (schemas, definitions). | no | yes |
| `Read` | Retrieve data without modifying state. | no | yes |
| `Transform` | Take input, return a modified result. | no | no |
| `Trigger` | Initiate a process/workflow. | yes | no |
| `Write` | Persist or update stored data. | yes | no |

If the operation is `Write`/`Trigger` and the effect is hard to undo, also set
`destructive: TRUE`.

## ExecutableResult

`src/ExecutableResult.php`. The only valid return type of `doExecute()`.

```php
ExecutableResult::success(TranslatableMarkup $message, ?array $context_values = []): static
ExecutableResult::failure(TranslatableMarkup $message, ?array $context_values = []): static
```

- `$message` — human/agent-readable summary. Make failure messages diagnostic.
- `$context_values` — keyed array. After a successful `execute()`, values whose
  key matches an `output_definitions` key become tool outputs (readable via
  `$tool->getOutputValue('key')`); other keys are ignored. So **every value you
  want to expose must have a matching output definition.**
- Wrap real work in `try/catch` and return `::failure()` with the exception
  message — don't let unexpected exceptions escape (though `execute()` is a
  safety net, explicit handling gives better messages).

## Input definitions

`Drupal\tool\TypedData\InputDefinition` (`src/TypedData/InputDefinition.php`):

```php
new InputDefinition(
  data_type: 'string',        // 'string','integer','boolean','entity:node','map','list', ...
  label: new TranslatableMarkup('...'),
  description: new TranslatableMarkup('...'),
  required: TRUE,             // default TRUE
  default_value: NULL,
  constraints: [              // see references/constraints.md
    'Choice' => ['choices' => ['ASC', 'DESC']],
  ],
  locked: FALSE,              // TRUE → fixed to default, not settable by caller
);
```

Specialized subclasses (same package):

- `MapInputDefinition` — structured object input; takes `property_definitions`
  (nested `InputDefinition`s).
- `ListInputDefinition` — list input; takes an `item_definition`.
- `EntityInputDefinition` — entity input; `data_type` like `'entity:node'`.

An input that takes an array of values is a `ListInputDefinition`. Its own
constraints, such as `Count`, bind the list; the constraints of each value go on
its `item_definition`:

```php
new ListInputDefinition(
  label: new TranslatableMarkup('Tags'),
  description: new TranslatableMarkup('...'),
  required: FALSE,
  constraints: ['Count' => ['max' => 10]],       // binds the list
  item_definition: new InputDefinition(
    data_type: 'string',
    label: '',
    description: '',
    constraints: ['Length' => ['max' => 64]],    // binds each value
  ),
);
```

Do not declare `multiple: TRUE` on an input: it is deprecated, converts to a
list with a deprecation notice, and is removed before rc1
([#3583049](https://git.drupalcode.org/project/tool/-/work_items/3583049)).

For a tool that acts on an entity, use `EntityInputDefinition` (or `data_type:
'entity:<type>'`): the caller supplies the entity and `doExecute()` receives the
**loaded entity object** in `$values['<input>']` — the `tool_belt` content tools
(`EntityDelete`, `EntitySave`, `FieldSetValue`, …) gate access on, and operate
directly on, that object. Don't re-load it from an id inside `doExecute()`.

Outputs use `Drupal\tool\TypedData\OutputDefinition` (data_type + label +
description; `description` is required, and there is no `locked`). The same
specialized subclasses exist as for inputs: `MapOutputDefinition` (properties
must be output definitions), `ListOutputDefinition`, `EntityOutputDefinition`.
Do not declare outputs with core `ContextDefinition` classes — that is
deprecated, and
[#3583050](https://git.drupalcode.org/project/tool/-/work_items/3583050)
removes it before rc1.

## How a Tool runs (mental model)

1. Caller `createInstance()`s the plugin and sets inputs via `setInputValue()`.
2. `execute()` calls `getExecutableValues()` (validates inputs against their
   constraints; invalid/missing required input → `\InvalidArgumentException`).
3. Your `doExecute($values)` runs and returns an `ExecutableResult`.
4. On success, declared outputs are populated from the result's context values.
5. Access is a separate path: `access()` → your `checkAccess()`.

Discovery: the `plugin.manager.tool` service scans `Plugin/tool/Tool/` in every
module's namespace. The base test classes use
`$this->container->get('plugin.manager.tool')`.
