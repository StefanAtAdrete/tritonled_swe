<?php

namespace Drupal\tool\TypedData;

/**
 * Holds the example values of an input or output definition.
 */
trait ExamplesTrait {

  /**
   * Example values for the definition.
   *
   * @var array
   *   Illustrative values emitted as the JSON Schema examples keyword.
   */
  protected array $examples = [];

  /**
   * {@inheritdoc}
   */
  public function getExamples(): array {
    return $this->examples;
  }

  /**
   * {@inheritdoc}
   */
  public function setExamples(array $examples): static {
    $this->examples = $examples;
    return $this;
  }

}
