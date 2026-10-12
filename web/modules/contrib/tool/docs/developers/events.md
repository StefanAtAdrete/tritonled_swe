# Events

The Tool module dispatches events so other modules can transform values and
definitions based on the invoker. This is how the same tool behaves differently for a
deterministic caller, an AI agent, and a remote MCP client without the tool's own
code changing.

## The invoker

An invoker identifies the context calling a tool. You set it when creating a tool, or
with `setInvoker()`:

```php
$tool = $this->toolManager->createInstance('greeting_tool', [], 'ai_agents');
```

Every transform event carries the invoker as an `Invoker` object, so a subscriber
can apply a transformation only for the callers it cares about, and leave direct
programmatic calls untouched:

```php
if ($event->getInvoker()?->id !== 'ai_agents') {
  return;
}
```

An invoker can also declare what it needs from Tool API. Pass enum cases as
capabilities and Tool API's built-in subscribers act on them:

```php
$tool = $this->toolManager->createInstance(
  'greeting_tool',
  [],
  new Invoker('ai_agents', InvokerCapability::EntitiesAsHandles),
);
```

Any enum case is accepted, so a module can define capabilities for its own
subscribers and check them with `$event->getInvoker()?->has(MyCapability::Case)`.

## Transform events

These fire around execution and change the values that flow in and out.

### ToolInputTransformEvent

Dispatched from `setInputValue()`, before the value is stored. Use it to convert an
incoming value for the current invoker, for example resolving a handle back into a
real entity. The event exposes `getInputName()`, `getValue()`, `setValue()`,
`getTool()`, and `getInvoker()`.

### ToolOutputTransformEvent

Dispatched per output value from `getFormattedResult()`, after execution. Use it to
convert an outgoing value for the invoker, for example encoding an entity as a handle
or reducing a large value for a language model. A subscriber can also attach hint
messages that travel with the formatted result.

The base class applies these only through `getFormattedResult()`. Call `getResult()`
instead when you want the raw, untransformed values.

## Definition normalize events

These change how a tool's definitions are serialized, rather than the runtime values.

- **ToolInputDefinitionNormalizeEvent** and **ToolOutputDefinitionNormalizeEvent**
  let subscribers adjust the normalized form of input and output definitions, for
  example when producing the JSON schema an AI function or MCP tool advertises.

## A built in subscriber

`RecursiveToolValueTransformSubscriber` walks nested structures (maps and lists) and
applies value transforms recursively, so a transform that applies to a scalar type
also applies when that type appears inside a map or list. This means an entity nested
several levels deep in a structured output is still swapped for a handle.

## Registering a subscriber

Register an event subscriber the standard Drupal way, tagged `event_subscriber` in
your module's `*.services.yml`, and subscribe to the event classes above from
`Drupal\tool\Event`.
