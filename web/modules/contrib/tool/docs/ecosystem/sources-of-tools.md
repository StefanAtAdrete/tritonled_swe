# Sources of tools

The Tool module ships no tools of its own. These modules provide tools, either by
defining Tool API plugins directly or by bringing in tools from external systems. Any
of the [modules that consume the Tool API](modules-using-tool-api.md) can then run
them.

## MCP Client

[MCP Client](https://www.drupal.org/project/mcp_client) connects Drupal to external
Model Context Protocol servers, so their capabilities (file systems, databases, APIs)
become available inside Drupal. It integrates with the AI module and exposes remote
MCP tools as function calling tools, supporting HTTP and local process transports and
multiple simultaneous server connections. In effect it turns any MCP server into a
source of tools for your site.

## Tool Belt

[Tool Belt](https://www.drupal.org/project/tool_belt) provides a collection of tools
for common Drupal core actions, built on the Tool API. Its submodules cover content
management, entity manipulation, user administration, system operations, content
translation, and workspaces. It works as a foundation toolkit for AI and automation
systems such as ECA.

## MCP Tools

[MCP Tools](https://www.drupal.org/project/mcp_tools) connects Drupal to AI
assistants through the Model Context Protocol, letting users describe actions in plain
language for the assistant to execute. It ships a large catalog of tools across many
submodules, covering site building, content management, translations, views, and
administration. The tools integrate with the Tool API and can be served over MCP or
used as plain Tool API plugins by other systems.
