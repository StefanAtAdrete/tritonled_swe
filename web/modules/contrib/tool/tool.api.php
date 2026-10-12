<?php

/**
 * @file
 * Hooks provided by the Tool API module.
 */

/**
 * Alters the discovered typed data adapter plugin definitions.
 *
 * Typed data adapters map typed data definitions to form elements when tool
 * inputs are rendered as forms.
 *
 * @param array $definitions
 *   The adapter plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\tool\TypedData\Adapter\TypedDataAdapterManager
 */
function hook_tool_typed_data_adapter_info_alter(array &$definitions): void {
  if (isset($definitions['text'])) {
    $definitions['text']['weight'] = 10;
  }
}

/**
 * @defgroup tool_entity_handles Entity handles
 * @{
 * Passing content entities across a connector boundary as handle strings.
 *
 * A connector that cannot carry entity objects (an LLM function call, an MCP
 * tool call) asks for handles when it creates the tool, by passing an
 * \Drupal\tool\Tool\Invoker that carries
 * \Drupal\tool\Tool\InvokerCapability::EntitiesAsHandles. Tool API then, for
 * that tool instance:
 * - resolves handle strings to entities on input,
 * - stores entity outputs and replaces them with handle strings, adding a
 *   hint the model can read,
 * - describes entity inputs and outputs as strings in the normalized schema.
 *
 * @code
 * $tool = $this->toolManager->createInstance(
 *   $plugin_id,
 *   [],
 *   new Invoker('my_connector', InvokerCapability::EntitiesAsHandles),
 * );
 * @endcode
 *
 * No subscriber or service registration is needed. Subscribers of your own
 * can still gate on the invoker with $event->getInvoker()?->id, and can check
 * for capabilities from any enum with $event->getInvoker()?->has().
 *
 * @see \Drupal\tool\EventSubscriber\EntityHandleTransformSubscriber
 * @see \Drupal\tool\Handle\EntityHandleTransformer
 * @see \Drupal\tool\EventSubscriber\RecursiveToolValueTransformSubscriber
 * @}
 */

/**
 * @defgroup tool_invoker_capabilities Invoker capabilities
 * @{
 * When a boundary behavior is a capability, and when it is the default.
 *
 * An \Drupal\tool\Tool\Invoker is the caller's context for the transform
 * pipeline: the events are the wide extension point any module can listen
 * to, and capabilities are the narrow signal a caller sends to the shared
 * subscribers Tool API and other modules provide. Three rules keep that
 * signal small:
 *
 * 1. A capability exists only when a real invoker must be able to decline
 *    the behavior. If every wire connector wants it, it is the default of
 *    the subscriber, not a flag on the invoker.
 * 2. No preset invokers (a factory that bundles several capabilities) until
 *    a second capability exists.
 * 3. No move of value transforms into the serializer until a second value
 *    transform is actually needed. Definition normalization already runs
 *    through the serializer; values are transformed by events.
 *
 * Capabilities are enum cases typed as \UnitEnum, so a module defines its
 * own enum for the behavior its subscribers gate on and checks it with
 * Invoker::has(). Cases from different enums never collide.
 *
 * @see \Drupal\tool\Tool\Invoker
 * @see \Drupal\tool\Tool\InvokerCapability
 * @}
 */
