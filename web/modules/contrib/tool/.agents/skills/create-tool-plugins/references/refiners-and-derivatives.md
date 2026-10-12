# Input-definition refiners & derivatives

Two different "dynamic" mechanisms. They solve different problems — don't
confuse them.

- **Refiner** — *one* tool whose input definitions change based on the values of
  *other inputs of the same tool* (e.g. the valid `bundle` choices depend on the
  chosen `entity_type_id`).
- **Deriver** — turns *one* plugin class into *many* discovered plugins (e.g. one
  "operate on entity type X" class that appears once per entity type). Use it
  instead of copy-pasting near-identical plugins.

---

## Input-definition refiners

### When

An input's constraints/choices/type can only be known once another input has a
value. Classic case: `bundle` is only valid relative to a chosen
`entity_type_id`; `fields` choices depend on `entity_type_id` + `bundle`.

### Wiring

1. Implement `Drupal\tool\TypedData\InputDefinitionRefinerInterface`.
2. Declare the dependencies in the attribute: `'input' => ['dep1', 'dep2', ...]`.
   The refiner for `'input'` runs once all listed dependencies have validated
   values. **If you declare `input_definition_refiners` but don't implement the
   interface, the attribute throws `\InvalidArgumentException` at discovery.**
3. Implement `refineInputDefinition()`: mutate or replace the definition for the
   named input, using the already-provided `$values`, and return it.

```php
#[Tool(
  id: 'my_module:entity_list',
  // ...
  input_definitions: [
    'entity_type_id' => new InputDefinition(data_type: 'string', /* ... */),
    'bundle' => new InputDefinition(data_type: 'string', required: FALSE, /* ... */),
    'fields' => new ListInputDefinition(
      required: FALSE,
      item_definition: new InputDefinition(data_type: 'string', label: '', description: ''),
      /* ... */
    ),
  ],
  input_definition_refiners: [
    'bundle' => ['entity_type_id'],
    'fields' => ['entity_type_id', 'bundle'],
  ],
)]
final class EntityList extends ToolBase implements InputDefinitionRefinerInterface {

  /**
   * {@inheritdoc}
   */
  public function refineInputDefinition(string $name, InputDefinitionInterface $definition, array $values): InputDefinitionInterface {
    switch ($name) {
      case 'bundle':
        // Add a constraint now that we know the entity type.
        $definition->addConstraint('EntityBundleExists', $values['entity_type_id']);
        break;

      case 'fields':
        ['entity_type_id' => $type, 'bundle' => $bundle] = $values;
        $fields = $this->entityFieldManager->getFieldDefinitions($type, $bundle);
        // Replace the list with a fully-specific one. The Choice goes on the
        // item definition, where it binds each field name.
        $definition = new ListInputDefinition(
          label: $definition->getLabel(),
          description: $definition->getDescription(),
          required: $definition->isRequired(),
          item_definition: new InputDefinition(
            data_type: 'string',
            label: '',
            description: '',
            constraints: ['Choice' => ['choices' => array_keys($fields)]],
          ),
        );
        break;
    }
    return $definition;
  }
}
```

Two ways to refine: `addConstraint()` (narrow the existing definition), or
replace it with a fully-specific definition object (e.g. swap in a settings
schema once the field type is known). Both are valid; the live `tool_belt`
examples (`EntityList`, `FieldAdd`, `EntityStub`, `FieldSetValue`,
`EntityBundleAdd`) do both.

A list input keeps its shape either way. Replace it with a `ListInputDefinition`,
never with a definition declaring `multiple: TRUE`: that is deprecated and only
converts back to a list with a deprecation notice. To narrow a list in place,
add the constraint to its item definition (`getItemDefinition()`); a constraint
on the list itself binds the whole value, not each item.

---

## Derivatives

### When

You'd otherwise write several plugins that differ only by a parameter — one per
entity type, per provider, per configured connection. Write **one** plugin class
plus a deriver and let Drupal generate the variants, rather than copy-pasting
near-identical classes. This is the stated preference: *prefer a deriver over
multiple single plugins.*

### What's supported

- The `#[Tool]` attribute accepts a `deriver:` class-string, and `ToolDefinition`
  implements `DerivablePluginDefinitionInterface`, so the `tool` plugin manager
  (a `DefaultPluginManager`) discovers derivatives through the standard Drupal
  mechanism.
- The deriver is an ordinary Drupal deriver: a class extending
  `Drupal\Component\Plugin\Derivative\DeriverBase` and implementing
  `Drupal\Core\Plugin\Discovery\ContainerDeriverInterface` (so it can use
  services), returning one derivative definition per variant from
  `getDerivativeDefinitions()`.
- The resulting plugin ids are `base_id:derivative_id`; at runtime the plugin
  reads which variant it is with `$this->getDerivativeId()`.

### Before you rely on it

There is **no deriver example in this codebase**, so this path is unproven here.
Follow the canonical Drupal deriver documentation, and before committing:

- Read `src/Tool/ToolDefinition.php` in the `tool` module to confirm which
  properties you can vary per derivative and how a derivative `ToolDefinition`
  must be produced — it is an **object**, not a plain array, which affects how
  the derivative discovery decorator merges base and derivative definitions.
- Add a kernel test asserting `plugin.manager.tool` exposes your expected
  derivative ids, then the usual unit test for `checkAccess()` and kernel test
  for `doExecute()` against at least one derivative.

If derivatives won't discover cleanly, fall back to separate plugins and note
why — a working set of plain plugins beats a broken deriver.
