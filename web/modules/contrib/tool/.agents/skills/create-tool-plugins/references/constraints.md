# Common input constraints

Constraints validate inputs *before* `doExecute()` runs. A failing constraint
makes `execute()` short-circuit to an "invalid input" failure result, so good
constraints mean `doExecute()` can trust its `$values`. Declare them on an
`InputDefinition`, or add them dynamically in a refiner (see
`references/refiners-and-derivatives.md`).

```php
new InputDefinition(
  data_type: 'string',
  label: new TranslatableMarkup('Sort order'),
  description: new TranslatableMarkup('ASC or DESC.'),
  required: FALSE,
  default_value: 'DESC',
  constraints: [
    'Choice' => ['choices' => ['ASC', 'DESC']],
  ],
);
```

The array is keyed by **constraint plugin id** → options. Multiple constraints:
just add more keys.

## Frequently useful constraints

| Id | Purpose | Typical options |
|----|---------|-----------------|
| `Choice` | Restrict to a fixed allowlist set. | `['choices' => [...]]` |
| `Range` | Numeric bounds. | `['min' => 0, 'max' => 100]` |
| `Length` | String length bounds. | `['min' => 1, 'max' => 255]` |
| `Regex` | Pattern match. | `['pattern' => '/^[a-z_]+$/']` |
| `Email` | Valid email. | — |
| `NotNull` / `Required` | Presence (usually prefer `required: TRUE` on the definition). | — |
| `PluginExists` | Value is a valid plugin id of a given manager (optionally implementing an interface). | `['manager' => 'entity_type.manager', 'interface' => ContentEntityInterface::class]` |
| `EntityBundleExists` | Value is a real bundle of an entity type. | usually added in a refiner: `addConstraint('EntityBundleExists', $entity_type_id)` |
| `FieldExists` | Field exists on an entity type + bundle. **(Defined by the `tool` module.)** | `['entityTypeId' => 'node', 'bundle' => 'article']` |

`FieldExists` lives at
`src/Plugin/Validation/Constraint/FieldExistsConstraint.php` in the `tool`
module.
Everything else above is a Drupal core / Symfony validation constraint.

## Real examples from `tool_belt`

```php
// Allowlist (EntityList sort_order):
'sort_order' => new InputDefinition(
  data_type: 'string',
  // ...
  default_value: 'DESC',
  constraints: ['Choice' => ['choices' => ['ASC', 'DESC']]],
),

// Plugin-id + interface (EntityList entity_type_id):
'entity_type_id' => new InputDefinition(
  data_type: 'string',
  // ...
  constraints: [
    'PluginExists' => [
      'manager' => 'entity_type.manager',
      'interface' => ContentEntityInterface::class,
    ],
  ],
),

// Added dynamically in a refiner once the entity type is known:
$definition->addConstraint('EntityBundleExists', $values['entity_type_id']);
```

## Picking constraints

- Prefer a **constraint over a hand-rolled check in `doExecute()`** — it
  validates earlier, is reusable, and surfaces in the input definition the agent
  reads. Keep `doExecute()` for logic the validator can't express.
- Use `required: TRUE` / `default_value:` on the definition for presence and
  defaults rather than constraints.
- Before relying on a constraint id, confirm it exists: it must be a
  discoverable `@Constraint` plugin. Grep core and the project:
  `grep -rP "id:\s*'TheId'" web/core web/modules`. Don't assume a Symfony
  constraint is registered as a Drupal plugin unless you find its id.
```
