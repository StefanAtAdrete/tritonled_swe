# For developers

The Tool module is a plugin type. You extend it by writing tool plugins in your own
module, and you integrate with it by discovering and running those plugins through
the tool manager.

## Extension points

- **Tool plugins.** Define a tool by adding a class under
  `src/Plugin/tool/Tool/` with the `#[Tool]` attribute, extending `ToolBase`. See
  [Creating a tool](creating-a-tool.md).
- **Typed data adapters.** The type system that maps input and output definitions to
  form elements and config schema is itself a plugin type,
  `Plugin/tool/TypedData/Adapter/`. Add an adapter to support a new data type. See
  [Input and output definitions](input-output-definitions.md).
- **Events.** Subscribe to input and output transform events to change values based
  on the invoker, and to normalize events to change how definitions are serialized.
  See [Events](events.md).

## Core services

| Service | Purpose |
|---|---|
| `plugin.manager.tool` | Discovers, instantiates, and runs tool plugins. |
| `plugin.manager.tool.typed_data_adapter` | Maps data definitions to adapters. |
| `tool.handle_store` | Stores and resolves opaque handles for values. |
| `tool.entity_handle_transformer` | Swaps entities for handles and back. |
| `tool.definition_serializer` | Serializes tool definitions. |

## The tool lifecycle

1. A caller asks `plugin.manager.tool` for a tool instance, optionally passing an
   invoker identifier.
2. The caller sets input values. Each `setInputValue()` dispatches a
   `ToolInputTransformEvent`, so subscribers can transform the value for the invoker
   before it is stored.
3. The caller calls `execute()`. The tool validates inputs, runs its `doExecute()`
   logic, and captures an `ExecutableResult`.
4. The caller reads the result. `getResult()` returns the raw result;
   `getFormattedResult()` applies per output transforms and returns a
   `FormattedExecutableResult` ready for the invoker.

The invoker is the key idea: the same tool produces raw values for a deterministic
caller and transformed, handle backed values for an AI or remote caller, without the
tool's own code branching on the caller.

## Invoking a tool

An invoker - anything that runs a tool for a caller, such as the `tool:run`
command, the Explorer's execute form, or an AI connector - follows one sequence:

1. **Check the declared permission** with
   `ToolManager::checkPermission($definition, $account)` before processing any
   caller-supplied value. The check reads only the definition, so it needs no
   plugin instance - a caller that has not instantiated the tool yet checks
   `$tool_manager->getDefinition($id)` before doing so. Report a denial the way
   an `access()` denial is reported.
2. **Set the caller's inputs** with `setInputValue()`.
3. **Validate** with `validateInputs()` and report the violations, so an input
   problem is never reported as an access problem. The Explorer's execute form
   does not yet take this step - its widgets constrain the input shape, and a
   value they cannot constrain fails closed inside `access()` - so of the
   in-tree invokers, `tool:run` and the AI connector model the full sequence.
4. **Check access** with `access()`, which re-checks the permission and then asks
   the tool's own `checkAccess()` with the resolved values.
5. **Execute** with `execute()` and read the result.

The order also bounds what each stage may reveal. Before the permission check
passes, the caller's values must not be processed at all: no transform events, no
violation messages composed from them, and no schema echo. After it, shape
feedback is the intended courtesy - violations against the advertised schema and
the correctable-failure schema echo - since the schema is already advertised at
that gate. A values-dependent `checkAccess()` can only deny after the values were
validated, so keeping existence-flavored detail out of constraint messages is the
tool author's responsibility; see [Creating a tool](creating-a-tool.md).

Skipping step 1 loses the early denial, not the gate: `access()` still checks the
declared permission first. What it protects is everything input processing does
for an account that was never allowed to invoke the tool - entity loading from
handle resolution, refiners querying storage, violation text built from the
values.
