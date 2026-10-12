# Tool

The Tool module provides a plugin type for defining well described, self contained
actions. A tool declares what it does, the typed inputs it accepts, and the typed
outputs it returns. Deterministic callers (Drush, controllers, ECA), non
deterministic callers (AI agents), and remote callers (MCP servers) all run the same
tool through the same interface.

The module ships no tools of its own. It provides the plugin type and the runtime;
tools come from other modules.

## Submodules

- **Tool Explorer** (`tool_explorer`): an admin UI to browse and execute registered
  tools.
- **Tool - AI Connector** (`tool_ai_connector`): exposes tools to the AI module as
  function calls. Experimental.

## Agent skills

The module ships two [Agent Skills](https://agentskills.io/specification) under
`.agents/skills/`: `use-drupal-tools` teaches an agent to find, inspect, and run
tools with the Drush commands, and `create-tool-plugins` walks it through
scaffolding a new plugin. See [`docs/usage/agent-skills.md`](docs/usage/agent-skills.md).

## Requirements

- Drupal 10.5 or 11.
- PHP 8.2 or newer.

## Documentation

Full documentation is under [`docs/`](docs/) and published at
https://project.pages.drupalcode.org/tool.
