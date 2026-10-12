<?php

declare(strict_types=1);

namespace Drupal\tool\TypedData;

/**
 * Interface for tools that support input refinement.
 *
 * Tools implementing this interface can dynamically refine their input
 * definitions based on the values of other inputs.
 *
 * Refinement may only narrow the value space. The unrefined definition
 * produces the schema invokers advertise (e.g. MCP tools/list), which is
 * static and validated against strictly before the tool is reached, so the
 * refined definition must accept a subset of what that advertisement
 * accepts. Concretely, a refiner may add constraints, declare properties on
 * a free map, supply a list's item definition, tighten required, or set a
 * default — and it must never change the data type or the multiplicity,
 * which would make the advertised and accepted shapes disagree. Two
 * narrowing type changes are permitted: an 'any' definition may refine to
 * any concrete type (an untyped schema accepts every value), and an entity
 * type may refine to one of its sub-types ('entity' to 'entity:node'). The
 * contract is enforced when refiners are applied.
 */
interface InputDefinitionRefinerInterface {

  /**
   * Refine input definitions based on current input values.
   *
   * This method is called when input refinement is needed, allowing tools
   * to dynamically modify their input definitions based on the current
   * values of other inputs.
   *
   * @param string $name
   *   The name of the input to be refined.
   * @param \Drupal\tool\TypedData\InputDefinitionInterface $definition
   *   The input definition to be refined.
   * @param array $values
   *   An associative array of current input values, keyed by input names.
   *
   * @return \Drupal\tool\TypedData\InputDefinitionInterface
   *   The refined input definition. Must accept a subset of the values the
   *   given definition accepts; see the interface-level contract.
   */
  public function refineInputDefinition(string $name, InputDefinitionInterface $definition, array $values): InputDefinitionInterface;

}
