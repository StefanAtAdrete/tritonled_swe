# Tool

The Tool module provides a plugin type for defining well described, self contained
actions. A tool declares what it does, the typed inputs it accepts, and the typed
outputs it returns. Any caller can then discover a tool, read its definition, check
access, and execute it without knowing how it was implemented.

Tools are the common contract between the code that *offers* an action and the code
that *invokes* it. A deterministic caller (Drush, a controller, an ECA workflow), a
non-deterministic caller (an AI agent), and a remote caller (an MCP server) all run
the same tool through the same interface.

## Key features

- A `Tool` attribute and `ToolBase` class for defining tools as attribute based plugins.
- Typed input and output definitions built on Drupal's Typed Data and Context
  systems, with validation and config schema.
- A `ToolOperation` taxonomy (Explain, Read, Transform, Trigger, Write) that
  describes the nature of each tool, whether it modifies state, and whether it is
  idempotent.
- An invoker aware execution flow, so inputs and outputs can be transformed based on
  who is calling (for example, encoding an entity as a token for an LLM).
- An entity handle store that swaps real entities for opaque handles when a value
  leaves the trusted boundary.
- Drush commands to list, search, inspect, and run tools.
- A submodule, [Tool Explorer](usage/tool-explorer.md), that adds an admin UI for
  browsing and executing tools.
- A submodule, [Tool - AI Connector](usage/ai-function-calling.md), that exposes
  tools to the [AI module](https://www.drupal.org/project/ai) as function calls.

## Where to start

- Site builders: read [Installation](installation.md), then explore what is
  registered on your site with the [Tool Explorer](usage/tool-explorer.md) or the
  [Drush commands](usage/drush.md).
- Developers: read [Creating a tool](developers/creating-a-tool.md).
- Looking for tools to install, or a system to run them through: see the
  [Ecosystem](ecosystem/modules-using-tool-api.md) pages.

The Tool module ships no tools of its own. It provides the plugin type and the
runtime; tools come from other modules. See
[Sources of tools](ecosystem/sources-of-tools.md).
