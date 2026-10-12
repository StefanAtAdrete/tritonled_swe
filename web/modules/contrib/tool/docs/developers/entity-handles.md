# Entity handles

When a tool value leaves the trusted boundary (for example, an entity returned to a
language model, or referenced by a remote MCP client), passing the real object is
both unsafe and unhelpful. The Tool module solves this with **handles**: opaque
tokens that stand in for a value and can be resolved back later.

## The problem

A model or remote caller should not receive a serialized entity with all its
internals. But across a multi step conversation it still needs a stable reference so a
later call can say "update the thing you just returned". A handle is that stable,
opaque reference.

## The handle store

`tool.handle_store` (`ToolHandleStoreInterface`) stores a value and returns a handle,
and resolves a handle back into the stored value. It is backed by the private temp
store and keyed per user, so handles are scoped to the session that created them.

## The entity handle transformer

`tool.entity_handle_transformer` (`EntityHandleTransformer`) applies handles to
entities specifically:

- On output, it replaces an entity with a handle before the value crosses the invoker
  boundary.
- On input, it resolves a handle back into the entity before the tool runs.

It works together with the [transform events](events.md): output transform swaps
entity to handle, input transform swaps handle back to entity. Because the
`RecursiveToolValueTransformSubscriber` walks nested structures, entities nested
inside maps and lists are handled too.

## Error cases

The handle API raises typed exceptions you can catch:

- `HandleNotFoundException` when a handle does not resolve, for example if it expired
  from the temp store.
- `InvalidHandleException` when a value is not a valid handle.
- `UnexpectedHandleValueException` when a resolved handle holds a value of the wrong
  type.

All three implement `HandleExceptionInterface`. A bad handle is always the invoker's
argument to fix, never a system failure, so a consumer that classifies its own
failures by exception type should catch this interface and treat it the same way it
treats other invalid-argument errors, rather than letting it fall into a generic
`catch (\Exception $e)` and get misclassified as unfixable (#3582993). See
`ToolPluginBase::execute()` in `tool_ai_connector` for the reference implementation.

## When you need this

Most tools do not interact with handles directly. Handles matter when you build a
consumer that sits at the invoker boundary, such as an AI connector or an MCP server,
and you need entity references to survive round trips without exposing entity data.
