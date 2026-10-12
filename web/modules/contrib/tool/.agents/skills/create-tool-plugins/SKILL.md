---
name: create-tool-plugins
description: Use when creating, scaffolding, or reviewing a new Tool plugin for the `tool` module (a class extending ToolBase with a #[Tool] attribute under src/Plugin/tool/Tool/). ALWAYS create it by running `drush generate plugin:tool` (alias `drush generate tool`) — never hand-write the plugin or its tests. Covers the generator, the #[Tool] attribute contract, doExecute/checkAccess, input/output definitions, input-definition refiners, derivatives, the fail-closed access rule, and the mandatory PHPUnit unit test for checkAccess + kernel test for doExecute (with a Guzzle mock for API-backed tools).
---

# Creating Tool plugins

> **Create the plugin by running the generator — never by hand-writing it.**
> The command that creates the files (plugin + both tests) is:
>
> ```bash
> ddev drush generate plugin:tool \
>   --answer="my_module" --answer="Read User Data" \
>   --answer="my_module_read_user_data" --answer="ReadUserDataTool" \
>   --answer="Returns the stored profile data for a user." \
>   --answer="Read" --answer="no"
> ```
>
> Run it after Step 0 (scan) and Step 1 (contract); the seven `--answer` values
> are detailed in **Step 2**. **Do not** write a file under
> `src/Plugin/tool/Tool/` (or its tests) by hand, and **do not** read the
> reference files to reconstruct one from scratch. If you are about to author a
> tool `.php` file yourself, STOP and run the generator instead.

A **Tool** is a plugin extending `Drupal\tool\Tool\ToolBase`, declared with a
`#[Tool]` attribute, discovered from `src/Plugin/tool/Tool/`: typed inputs, typed
outputs, an access check, and an execution body. It's how an agent (or Drush, or
code) invokes a capability safely and predictably. The generator emits a correct,
standards-passing starting point (the `#[Tool]` attribute, an example
input/output, a working `doExecute()` stub, a **fail-closed** `checkAccess()`,
and the two mandatory test stubs) that you then customize.

The references below are for **customizing what the generator produced** — not for
writing a Tool by hand. Open one only when its step calls for it:

| Your step | Read |
|-----------|------|
| Understand the attribute, base class, inputs/outputs, ExecutableResult, operations | `references/anatomy.md` |
| Decide and write `checkAccess()` (the fail-closed rule, the patterns) | `references/access-control.md` |
| Flesh out the generated unit test (checkAccess) + kernel test (doExecute) + API mock | `references/testing.md` |
| Inputs depend on other inputs, OR you'd write near-identical plugins | `references/refiners-and-derivatives.md` |
| Look up an input-validation constraint | `references/constraints.md` |

## Hard requirements

These are the rules that make a Tool correct rather than merely runnable. Do not
skip them, do not defer them, do not rationalize around them.

1. **Generate, never hand-write.** Create the plugin and its tests by running
   `drush generate plugin:tool` (Step 2). Do not author the `.php` files by hand,
   and do not treat reading the references as a substitute for running it. The
   only correct way to start a Tool is the generator.
2. **Scan first.** Before generating, read the target module and the relevant
   `tool_belt` example plugins deeply. A Tool that ignores existing conventions,
   permissions, or services is wrong even if it runs. See *Step 0* below.
3. **Access is fail-closed.** The generator scaffolds a fail-closed
   `checkAccess()` (`@todo` + thrown `\LogicException`). Only replace it with a
   real body when you are **100% certain** which permission or access check
   belongs there. If you are not certain, leave it fail-closed — never silently
   allow. See `references/access-control.md`.
4. **Always get access approval.** Before committing to a `checkAccess()`
   strategy, present the exact permission(s)/access check to the user and get
   explicit approval. Use `AskUserQuestion`. Do not guess access.
5. **Tests are mandatory — the generator writes them; never delete them.**
   `drush generate plugin:tool` scaffolds both required tests next to the plugin:
   - A **PHPUnit unit test for `checkAccess()`** — `tests/src/Unit/Tool/`.
   - A **PHPUnit kernel test for `doExecute()`** — `tests/src/Kernel/Tool/`.

   Flesh them out to match the real contract. If the tool makes HTTP/API calls,
   the kernel test **must** mock them with a Guzzle `MockHandler`/middleware —
   never hit a live endpoint. See `references/testing.md`.
6. **Prefer a deriver over copy-paste.** If you would otherwise write several
   nearly identical plugins, write one plugin + a deriver instead. See
   `references/refiners-and-derivatives.md`.

## Workflow

Scaffold with the generator, then customize. The order is: agree the contract →
generate → fill in inputs/outputs and `doExecute()` → confirm and write real
access → make the generated tests real → verify.

### Step 0 — Scan the modules deeply (required)

Before designing anything:

- Read the **target module**: its `.info.yml`, `.services.yml`,
  `.permissions.yml`, existing plugins, and what services it already injects.
- Read 2–3 **existing Tool plugins** whose operation matches yours, to match
  their structure, naming, error handling, and `create()` injection style. Look
  first in the target module itself, then in any other installed module that
  ships tools (`grep -rl "use Drupal\\tool\\Attribute\\Tool" web/modules`).
  The richest set of worked examples is the companion **`tool_belt`** project
  (its submodules live under `tool_belt/modules/*/src/Plugin/tool/Tool/`), which
  the reference files quote throughout. `tool_belt` is a **separate, optional**
  project (<https://www.drupal.org/project/tool_belt>), **not** a dependency of
  `tool`: use it if it is installed, otherwise rely on the self-contained
  patterns in `references/` and the examples in the modules you do have. Do not
  install it or block on it.
- Identify the **operation type** (`Read`/`Write`/`Transform`/`Trigger`/
  `Explain`) and whether the tool is `destructive`.
- Identify the **permission or access model** the module already uses. Note it;
  you will confirm it with the user in Step 4.

State, in one or two sentences, what you learned before proceeding. If anything
is ambiguous (which module, which permission, single plugin vs. deriver), ask.

### Step 1 — Agree the contract

Nail down, with the user when unclear:

- id — a single token or `prefix:name`, where `prefix` is a stable namespace
  (often a module family, as in `tool_belt:*`); see `references/anatomy.md`.
- label, description, operation, destructive flag.
- **Inputs**: name, `data_type` (a list is a `ListInputDefinition`), required?,
  default, constraints.
- **Outputs**: what the tool returns for chaining.
- Whether any input's valid values depend on another input (→ refiner) and
  whether the shape generalizes to a deriver.

### Step 2 — Scaffold with the generator

Run it non-interactively: pass one `--answer` per prompt, in order. The answers
are positional, so include every one even to accept a default (repeat the default
value). Drop `ddev` if you don't run Drush through ddev:

```bash
ddev drush generate plugin:tool \
  --answer="my_module" \
  --answer="Read User Data" \
  --answer="my_module_read_user_data" \
  --answer="ReadUserDataTool" \
  --answer="Returns the stored profile data for a user." \
  --answer="Read" \
  --answer="no"
```

The seven `--answer` values, in order:

| # | Answer | Notes |
|---|--------|-------|
| 1 | Module machine name | The target module from Step 0. |
| 2 | Tool label | Human name with spaces, e.g. `Read User Data` — the Plugin ID and class defaults are derived from it. |
| 3 | Plugin ID | Defaults to `{module}_{label}`. A single token or `prefix:name`; see `references/anatomy.md`. |
| 4 | Plugin class | Defaults to `{Label}Tool`. |
| 5 | Tool description | The one sentence an agent reads to pick the tool. Make it count. |
| 6 | Operation | Exact case name: `Explain` / `Read` / `Transform` / `Trigger` / `Write`. |
| 7 | Destructive? | `yes` / `no` — `yes` only for irreversible Write/Trigger actions. |

- Only the long `--answer=` form works (Drush doesn't expose the `-a` short option).
- Add `--dry-run` to print the files without writing them — handy to confirm the
  derived Plugin ID and class first.
- Omit `--answer` flags to fall back to the interactive wizard (same order).

It writes three files:

- `src/Plugin/tool/Tool/{Class}.php` — the plugin, with one example
  input/output, a working `doExecute()` stub, and a **fail-closed
  `checkAccess()`** that throws until you define real access (intentional — see
  `references/access-control.md`).
- `tests/src/Unit/Tool/{Class}AccessTest.php` — asserts the fail-closed
  `checkAccess()` throws.
- `tests/src/Kernel/Tool/{Class}Test.php` — exercises `doExecute()` (happy path
  + missing-required-input).

Rebuild caches so the plugin is discovered, and run the stubs once to confirm
the scaffold is green before you change anything:

```bash
ddev drush cr
ddev exec ./vendor/bin/phpunit -c web/core web/modules/custom/{module}
```

### Step 3 — Fill in the contract and `doExecute()`

Replace the generated `example` input and `result` output with the real
`input_definitions` / `output_definitions` from Step 1 — see
`references/anatomy.md` for types, subclasses, and the entity-input rule,
`references/constraints.md` for validation, and
`references/refiners-and-derivatives.md` if an input's valid values depend on
another input or the shape generalizes to a deriver.

Then implement `doExecute()`: wrap real work in `try/catch` and return
`ExecutableResult::success()` / `::failure()` — never a bare array or string.
Every value you want to expose must have a matching `output_definitions` key or
it is dropped. Inject services by overriding `create()` (the constructor is
`final`; never try to override it) — see `references/anatomy.md`.

### Step 4 — Confirm the access strategy (STOP and ask)

The scaffold ships a fail-closed `checkAccess()` on purpose. Before you replace
it, present the proposed access to the user and get approval — the exact
permission string(s) and/or entity access op. Use `AskUserQuestion`. If you
cannot identify the correct access with certainty, **leave the fail-closed body**
and surface that to the user. Only after approval, write the real `checkAccess()`.
See `references/access-control.md`.

### Step 5 — Make the generated tests real

The two stubs already pass against the scaffold; now make them test the *real*
tool. Update them to the real inputs/outputs, and:

- **Unit test**: replace the fail-closed assertion with the allowed/denied cases
  for your approved access check.
- **Kernel test**: assert the real success message and outputs, invalid/missing
  input, and any side effects (entity saved/deleted, message logged, …). For
  API-backed tools, wire a Guzzle `MockHandler` in `setUp()` — never hit a live
  endpoint.

Follow `references/testing.md` exactly.

### Step 6 — Verify

```bash
ddev drush cr
ddev exec ./vendor/bin/phpunit -c web/core web/modules/custom/{module}
```

Confirm green. If you left the fail-closed access body, its unit test stays green
(it asserts the throw) and the tool remains unusable until access is real —
exactly the intent; it must not ship silently allowed. Then run the module's
coding-standards and static-analysis checks if it ships them (the `tool` module
has `phpcs.xml` and `phpstan.neon`): e.g. `vendor/bin/phpcs` and
`vendor/bin/phpstan analyse`.

## Common pitfalls

| Pitfall | Reality |
|---------|---------|
| Hand-writing the plugin from a blank file | Run `drush generate plugin:tool` first, then build on the scaffold. |
| Deleting the generated tests | Both tests are mandatory — flesh them out, never remove them. |
| Overriding the constructor to inject a service | The constructor is `final`. Override `create()` instead. |
| Returning a bare array / string from `doExecute()` | Return `ExecutableResult::success()` or `::failure()` only. |
| Output value not appearing after `execute()` | The key in `success(..., [...])` must match an `output_definitions` key, or it is dropped. |
| `input_definition_refiners` set but class doesn't implement `InputDefinitionRefinerInterface` | The attribute throws `\InvalidArgumentException` at discovery. Implement the interface. |
| Plugin not discovered | Wrong path/namespace (`src/Plugin/tool/Tool/`) or id doesn't match the `group`/`group:name` rule. `drush cr`. |
| Hardcoded user-facing strings | Wrap every user-facing string in `TranslatableMarkup`/`$this->t()`. |
| `checkAccess()` guessed | Leave it fail-closed and get approval. See rule 3 & 4. |
| Skipping a test "because it's simple" | Both tests are mandatory. Simple tools break too. |
