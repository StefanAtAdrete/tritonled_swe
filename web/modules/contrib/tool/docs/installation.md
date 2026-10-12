# Installation

## Requirements

- Drupal 10.5 or 11.
- PHP 8.2 or newer.

## Install the module

Use Composer to download the module, then enable it with Drush:

```bash
composer require drupal/tool
drush en tool
```

The base `tool` module depends only on Drupal core's Serialization module,
which is enabled automatically. On its own it adds the tool plugin type and the
Drush commands, but no tools and no user interface.

## Optional submodules

The module ships two submodules. Enable the ones you need.

### Tool Explorer

Adds an admin UI at Configuration to browse and execute registered tools.

```bash
drush en tool_explorer
```

No requirements beyond the base module. See [Tool Explorer](usage/tool-explorer.md).

### Tool - AI Connector

Exposes every registered tool to the [AI module](https://www.drupal.org/project/ai)
as a function call, so AI agents and chat can invoke them. This submodule is
experimental.

```bash
composer require drupal/ai
drush en tool_ai_connector
```

Requires the AI module (`ai:ai`) and core's Serialization module
(`drupal:serialization`), which Drush enables automatically. See
[AI function calling](usage/ai-function-calling.md).

## Verify the install

List the tools registered on your site:

```bash
drush tool:list
```

If you have only enabled the base module and no tool providing modules, this returns
no tools. Install a module that provides tools (see
[Sources of tools](ecosystem/sources-of-tools.md)) to populate the list.
