# Access control — `checkAccess()`

Access is the part of a Tool most likely to be wrong in a way that doesn't show
up in a quick test: a tool that runs is not the same as a tool that runs *for
the right user*. Treat this section as rigid.

## The two rules

### Rule 1 — Fail closed when uncertain

Only write a concrete `checkAccess()` body when you are **100% certain** which
permission or access check belongs there. Certainty means you can point to the
exact permission string (in a `*.permissions.yml`) or the exact entity access
operation and say *why* it is correct.

If you are not certain, do **not** invent something plausible, and do **not**
default to `allowed()`. Leave a `@todo` and **throw** — the tool must refuse to
run until a human defines access:

```php
protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
  // @todo Access for this tool has not been determined. Replace this with the
  //   correct permission or entity access check, then remove the exception.
  throw new \LogicException(sprintf(
    'Access control for %s has not been defined. Define checkAccess() before using this tool.',
    static::class,
  ));
}
```

This is exactly the body `drush generate plugin:tool` scaffolds, so in practice
you don't write it — you only **replace** it once Rule 2 is satisfied. Leaving it
in place is a valid, deliberate outcome, not an unfinished one.

Throwing (rather than returning `AccessResult::forbidden()`) is deliberate: a
forbidden result can be quietly overlooked and shipped; a thrown
`\LogicException` cannot. `execute()` will convert the throw into a failure
result, so there is no fatal error — but the tool is unusable until access is
real, which is exactly the intent.

### Rule 2 — Always get approval before writing a real body

Before you replace the fail-closed body with a real check, present the proposed
access to the user and get explicit approval. Use `AskUserQuestion` with the
concrete options you're choosing between — never decide access silently.

What to put in the prompt:

- The **exact** permission string(s) you propose, or the entity access
  operation (`view`/`update`/`delete`/`create`).
- Why it fits (which existing permission in the module's `*.permissions.yml`,
  or why entity access is the right gate).
- The alternatives you considered.

Only after approval do you write the concrete `checkAccess()` and the matching
unit test (see `references/testing.md`).

## The `checkAccess()` signature

```php
protected function checkAccess(
  array $values,                 // the executable input values
  AccountInterface $account,     // the account to check (already resolved)
  bool $return_as_object = FALSE,
): bool|AccessResultInterface {
  // Build an AccessResultInterface, then honour $return_as_object:
  return $return_as_object ? $result : $result->isAllowed();
}
```

Always honour `$return_as_object`: return the `AccessResultInterface` when TRUE
(callers need cacheability metadata), the boolean otherwise. Every pattern below
ends with that ternary.

## Approved patterns (from `tool_belt`)

The patterns below are transcribed from the optional companion project
`tool_belt` (<https://www.drupal.org/project/tool_belt>) so they stand on their
own here: you do **not** need `tool_belt` installed to use them.

Pick the one that genuinely matches; do not staple a permission check onto a
tool that should gate on entity access.

### Permission-based

```php
$access = AccessResult::allowedIfHasPermission($account, 'use log_message tool');
return $return_as_object ? $access : $access->isAllowed();
```

### Entity access (operation on a passed entity)

```php
$access = $values['entity']->access('delete', $account, TRUE);
return $return_as_object ? $access : $access->isAllowed();
```

### Create vs. update (new entity has no `update` access yet)

```php
$entity = $values['entity'];
if ($entity->isNew()) {
  $handler = $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'access');
  $access = $handler->createAccess($entity->bundle(), $account, [], TRUE);
}
else {
  $access = $entity->access('update', $account, TRUE);
}
return $return_as_object ? $access : $access->isAllowed();
```

### Combined permissions (`orIf`)

```php
$access = AccessResult::allowedIfHasPermission($account, 'administer ' . $values['entity_type_id'] . ' fields')
  ->orIf(AccessResult::allowedIfHasPermission($account, 'administer site configuration'));
return $return_as_object ? $access : $access->isAllowed();
```

### Dynamic admin permission from the entity type

```php
$definition = $this->entityTypeManager->getDefinition($values['entity_type_id']);
$access = ($perm = $definition->getAdminPermission())
  ? AccessResult::allowedIfHasPermission($account, $perm)
  : AccessResult::allowedIfHasPermission($account, 'administer site configuration');
return $return_as_object ? $access : $access->isAllowed();
```

### Field-level

```php
$entity = $values['entity'];
$allowed = $entity->get($values['field_name'])->access('edit', $account);
$access = $allowed ? AccessResult::allowed() : AccessResult::forbidden();
return $return_as_object ? $access : $access->isAllowed();
```

## Choosing the right gate

| The tool... | Gate on |
|-------------|---------|
| reads/loads/lists a specific entity | that entity's `view` (or `update`/`delete`) access |
| creates an entity of a bundle | `createAccess()` of that entity type/bundle |
| edits one field on an entity | that field's `edit` access |
| performs a site/admin operation (config, fields, bundles) | the relevant `administer ...` permission, ideally the entity type's `getAdminPermission()` |
| does something with no existing permission that fits | **STOP** — fail closed (Rule 1) and ask the user whether to add a new permission |

When no existing permission fits, the right move is usually to define a new one
in the module's `*.permissions.yml` — but that is a decision for the user, not a
default. Surface it (Rule 2); don't paper over it with `'access content'`.

## Declaring a new permission

When the approved gate is a *new* permission, declare it in the module's
`{module}.permissions.yml`, and use the **exact** key string in both
`checkAccess()` and the access unit test. Pattern (verified, from
`tool_belt_system`):

```yaml
# tool_belt_system.permissions.yml
use send_email tool:
  title: 'Use Send Email Tool'
  description: 'Allows users to send emails through the system.'
  restrict access: true
```

Then in the plugin:

```php
$access = AccessResult::allowedIfHasPermission($account, 'use send_email tool');
```

- The permission string in `allowedIfHasPermission()` must match the YAML key
  character-for-character.
- Add `restrict access: true` for sensitive or destructive tools — it flags the
  permission as security-sensitive on the admin Permissions page.
