# Modules using the Tool API

These modules consume tools. They discover tools registered through the Tool API and
run them as part of their own systems, whether that is an autonomous agent, an
event driven workflow, a visual flow, or a remote protocol server.

## AI Agents

[AI Agents](https://www.drupal.org/project/ai_agents) is a framework for building
text to action agents that manipulate Drupal configuration and content from natural
language instructions. It uses tool calling so agents can perform tasks like creating
fields, content types, and taxonomy terms without custom code. It ships several
pre built agents and integrates with Drupal's AI infrastructure to provide an agentic
system over content and configuration.

## ECA

[ECA Tool](https://www.drupal.org/project/eca_tool) integrates the Event Condition
Action (ECA) framework with the Tool API. It lets you run tools as part of ECA
models, so event driven, no code workflows can invoke any registered tool. This
connects ECA's automation to the growing catalog of tools on your site.

## FlowDrop

[FlowDrop](https://www.drupal.org/project/flowdrop) is a workflow orchestration
module with a visual, no code interface for building and tracking automated
processes. It ships many built in node processors for data transformation, entity
operations, and AI integration, keeps full audit trails, and supports human in the
loop approvals. It is designed to be AI ready without being AI dependent, running
tools and models within defined workflow graphs.

## MCP Server

[MCP Server](https://www.drupal.org/project/mcp_server) exposes Drupal to AI systems
through the Model Context Protocol over STDIO and HTTP transports. It creates MCP
tools from Tool API plugins through configuration, with support for resources,
prompts, sampling, and authentication, including OAuth 2.1. An admin interface manages
which tools are exposed, without coding.
