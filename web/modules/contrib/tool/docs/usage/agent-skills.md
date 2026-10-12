# Agent skills

The module ships two [Agent Skills](https://agentskills.io/specification) under
`.agents/skills/`. A skill is a `SKILL.md` file an AI coding agent loads on
demand when a task matches its description.

| Skill | What the agent learns |
| --- | --- |
| `use-drupal-tools` | Find a tool with `tool:search` or `tool:list --format=json`, read its JSON Schema and requirement status with `tool:info --format=json`, run it with `tool:run --json`, and pass `--uid` so the access check runs as the real caller. |
| `create-tool-plugins` | Scaffold a new `#[Tool]` plugin with `drush generate plugin:tool`, then fill in the input and output definitions, the fail-closed access check, and the unit and kernel tests. |

Both skills are procedural. The [Drush commands](drush.md) and
[Creating a tool](../developers/creating-a-tool.md) pages remain the reference
for each option; a skill tells the agent which command to reach for at each step
and which open bugs to work around.

## Getting the skills into a project

Agents read `.agents/skills/` at the project root, not inside `modules/contrib/`.
Two ways to get them there:

- [`drupal/ai_best_practices`](https://www.drupal.org/project/ai_best_practices)
  scans installed modules for `.agents/skills/` and syncs them into the project's
  `.agents/skills/` on `composer install`. `composer drupal-ai skills-list` shows
  the result with `webroot (tool)` as the source.
- Copy or symlink `web/modules/contrib/tool/.agents/skills/<name>/` into the
  project's `.agents/skills/` by hand.

## Modules that ship their own tools

A module that provides Tool plugins can ship its own skills the same way: put
them under `.agents/skills/<name>/SKILL.md` in the module. Keep those skills to
the domain steps (which tool, in what order, with which discovery tool for each
input) and point at `use-drupal-tools` for the Tool mechanics, so the boilerplate
lives in one place.
