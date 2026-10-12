# AI function calling

The Tool - AI Connector submodule (`tool_ai_connector`) exposes every registered
tool to the [AI module](https://www.drupal.org/project/ai) as a function call. Once
enabled, AI agents, the AI assistant, and chat features can discover and invoke your
tools the same way they invoke any other AI function.

!!! warning
    This submodule is experimental.

## Requirements and install

```bash
composer require drupal/ai
drush en tool_ai_connector
```

Requires the base `tool` module, the AI module (`ai:ai`), and core's Serialization
module (`drupal:serialization`), which Drush enables automatically.

## How it works

The connector registers a single AI function call plugin that derives one function
per tool. For a tool with plugin ID `send_email`, it creates an AI function whose
`function_name` is `tool__send_email`, and that is the name the model calls. The
tool's label becomes the function's human readable name in the admin UI, its
description becomes the function description, and its input definitions become the
function's parameters.

All derived functions are grouped under a **Tools API** function group, so you can
enable or restrict them together wherever the AI module lets you choose which
functions an agent may call.

## Complex inputs

When a tool declares a map input (a structured object), the connector normalizes it
into a nested object schema so the model receives the correct parameter shape rather
than an opaque value. This is handled through the AI module's tools property alter
hook.

## Handles at the boundary

When a tool returns an entity, exposing the raw entity to a language model is both
unsafe and unhelpful. The Tool module's [entity handle](../developers/entity-handles.md)
system swaps entities for opaque handles as values cross the invoker boundary, and
swaps them back when a later call passes a handle in. This keeps entity references
stable across a multi step agent conversation without leaking entity internals to the
model.
