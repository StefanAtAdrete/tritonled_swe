# Input and output definitions

A tool describes the data it accepts and returns with typed definitions. These
definitions drive validation, the execute form, config schema, and how the tool is
serialized for an AI function or MCP tool.

## Input definitions

Inputs are declared with `InputDefinition`, a typed-data definition (it extends
core's `DataDefinition`). Its constructor takes:

| Argument | Default | Purpose |
|---|---|---|
| `data_type` | required | The typed data type, for example `string`, `integer`, `boolean`, `email`, `entity`. |
| `label` | required | Human readable label. |
| `description` | required | What the input is for. |
| `required` | `TRUE` | Whether the input must be provided. |
| `multiple` | `FALSE` | Deprecated: declare a `ListInputDefinition` instead (see below). |
| `default_value` | `NULL` | Default when none is provided. |
| `constraints` | `[]` | Typed Data validation constraints. |
| `locked` | `FALSE` | Whether the value is fixed and not user editable. |

For structured and list inputs the module provides specialized definitions:

- `MapInputDefinition` for an object with named properties.
- `ListInputDefinition` for a repeated value.
- `EntityInputDefinition` for an entity reference.

A list is declared with `ListInputDefinition`: its own constraints, such as
`Count`, bind the list, and its item definition carries the constraints of each
item. Declaring an input with `multiple: TRUE` still loads, with a deprecation
notice: the plugin definition converts it to the list it means, and a map does
the same for a property declared that way. `Count` moves to the list and every
other constraint stays on the item, so the advertised schema does not change,
apart from two things: a default value is no longer advertised on each item,
and for an invoker taking entities as handles, the handle note of an entity list
moves from the array description to the item.
`multiple` is removed before rc1
([#3583049](https://git.drupalcode.org/project/tool/-/work_items/3583049)).
Convert your declarations now.

### Required, empty, and NotBlank

`required` means the value is present and not NULL. It says nothing about
size: an empty list (`[]`) and an empty map (`{}`) are values, and a required
list or map input accepts them, matching the JSON Schema the tool advertises,
where `required` only names the keys a caller must send. The same rule holds
for outputs: a required map output may return `{}`, and "zero results" is
still a result.

When an input must not be empty, declare that:

- `NotBlank` for "must not be empty", on a string, list, or map. It is
  advertised as `minLength: 1`, `minItems: 1`, or `minProperties: 1`.
- `Count` for exact bounds on a list's items or a map's properties,
  advertised as `minItems`/`maxItems` or `minProperties`/`maxProperties`.

```php
'nodes' => new ListInputDefinition(
  label: new TranslatableMarkup('Nodes'),
  description: new TranslatableMarkup('The nodes to export.'),
  constraints: ['NotBlank' => []],
  item_definition: new EntityInputDefinition('entity:node', ...),
),
```

Think about the empty value especially where it would replace stored data: a
tool that writes the settings a caller sends should either declare `NotBlank`
or treat an empty value as "nothing to do", so a caller's `{}` cannot wipe a
configuration by accident. A multiple declaration's constraints bind each
item, as they always did, and imply nothing for the list itself: to require
a non-empty list, declare `NotBlank` or `Count` on the list
([#3583069](https://git.drupalcode.org/project/tool/-/work_items/3583069)).

### Example values

An input definition can carry illustrative example values, emitted as the JSON
Schema `examples` keyword next to `default` when the definition is serialized.
Examples help a non-deterministic caller (an LLM function call, an MCP client)
produce well-formed values for inputs whose shape is not obvious from the type
and description alone. Declare them in the `#[Tool]` attribute:

```php
'field_name' => new InputDefinition(
  data_type: 'string',
  label: new TranslatableMarkup('Field name'),
  description: new TranslatableMarkup('The machine name of the field.'),
  examples: ['field_tags', 'field_summary'],
),
```

`setExamples()` sets them on an already built definition. Examples always
describe a single value: a list input declares them on its item definition, they
are emitted on the item schema (`items.examples`) and the array level stays
clean.

Examples are annotations, not validation constraints: they are emitted verbatim
and never validated or stripped at runtime. They are authored against the
definition as declared — an event subscriber or refiner that re-types an input
in place is responsible for updating or clearing its examples, the same
contract that applies to `default_value`. A subscriber that swaps in a whole
replacement definition sheds them automatically.

## Output definitions

Outputs are declared with `OutputDefinition`. It mirrors `InputDefinition`: the
constructor takes the same arguments in the same order, minus `locked`, and
`description` is required. `multiple` is deprecated here as well: an output or
map property declared with `multiple: TRUE` converts to a `ListOutputDefinition`
with the same deprecation notice, so declare the list instead.

Declaring an output with a core `ContextDefinition` or
`EntityContextDefinition` - or with an input definition class - still loads,
with a deprecation notice. The plugin definition converts it to the matching
`*OutputDefinition` class, a `multiple: TRUE` one to a `ListOutputDefinition` of
its data type, and synthesizes a description from the label when none is set.
This acceptance shim is temporary
([#3583050](https://git.drupalcode.org/project/tool/-/work_items/3583050)
removes it before rc1). Convert your declarations now.

For structured and list outputs the module provides matching definitions:

- `MapOutputDefinition` for an object with named properties. Every property must be
  an `OutputDefinitionInterface`.
- `ListOutputDefinition` for a repeated value.
- `EntityOutputDefinition` for an entity reference.

Each output your tool returns from `doExecute()` must have a matching definition,
keyed by the same name. The runtime sets only the output values that correspond to
a declared definition; extra values in the result are not exposed as outputs, and
output transforms are not applied to them.

## The type system: adapters

Each data type is mapped to a **typed data adapter**, a plugin under
`Plugin/tool/TypedData/Adapter/`. An adapter knows how to render a form element for
the type, how to extract a submitted value, and how to build a config schema
definition for it. The module ships adapters for:

| Adapter | Types it handles |
|---|---|
| `StringAdapter` | Strings. |
| `TextAdapter` | Long text. |
| `NumberAdapter` | Integers and floats. |
| `BooleanAdapter` | Booleans. |
| `EmailAdapter` | Email addresses. |
| `SelectAdapter` | A value from a fixed set of options. |
| `ListAdapter` | Lists of values. |
| `MapAdapter` | Structured maps. |
| `EntityAdapter` | Entity references. |
| `UndefinedAdapter` | Fallback for types without a specific adapter. |

To support a new data type, add an adapter plugin with the `#[TypedDataAdapter]`
attribute. The `plugin.manager.tool.typed_data_adapter` manager resolves the adapter
for a given data definition.

## Config schema

Adapters also produce the config schema for a tool's inputs. The
[Tool Explorer](../usage/tool-explorer.md) view page uses this to generate a
suggested `tool.plugin.<id>` schema when one is missing. The suggested root mapping
carries the `FullyValidatable` constraint, so once you add the schema Drupal
validates stored input values against their constraints.

## Refining inputs dynamically

A tool can adjust its input definitions based on the values of other inputs. To do
this, pass `input_definition_refiners` to the `#[Tool]` attribute and implement
`InputDefinitionRefinerInterface`. Its `refineInputDefinition($name, $definition, $values)`
method receives the input being refined and the current values, and returns the
refined definition. The attribute enforces the interface: declaring refiners on a
class that does not implement it throws at definition build time.

To replace the definition of a list input, return a `ListInputDefinition`. A
refined definition declaring `multiple: TRUE` still converts to the list it means,
with a deprecation notice, the same way a declaration does.
