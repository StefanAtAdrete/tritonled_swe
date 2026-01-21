<?php

namespace Drupal\tritonled_commerce_agent\Plugin\AiFunctionCall;

use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[FunctionCall(
  id: 'commerce_agent:list_attribute_values',
  function_name: 'commerce_agent_list_attribute_values',
  name: 'List Attribute Values',
  description: 'List all values for a specific product attribute (e.g., watt, cct)',
  group: 'commerce_tools',
  context_definitions: [
    'attribute' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Attribute'),
      description: new TranslatableMarkup('Attribute machine name (e.g., watt, cct)'),
      required: TRUE,
    ),
  ],
)]
class ListAttributeValues extends FunctionCallBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai.context_definition_normalizer'),
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  public function execute(array $parameters = []): array {
    try {
      $storage = $this->entityTypeManager
        ->getStorage('commerce_product_attribute_value');

      $query = $storage->getQuery()
        ->condition('attribute', $parameters['attribute'])
        ->accessCheck(TRUE)
        ->sort('weight', 'ASC')
        ->sort('name', 'ASC');

      $ids = $query->execute();
      
      if (empty($ids)) {
        return [
          'success' => TRUE,
          'attribute' => $parameters['attribute'],
          'values' => [],
          'message' => "No values found for attribute: {$parameters['attribute']}",
        ];
      }

      $values = $storage->loadMultiple($ids);
      $result = [];

      foreach ($values as $value) {
        $result[] = [
          'id' => $value->id(),
          'name' => $value->getName(),
          'weight' => $value->getWeight(),
        ];
      }

      return [
        'success' => TRUE,
        'attribute' => $parameters['attribute'],
        'count' => count($result),
        'values' => $result,
      ];
    }
    catch (\Exception $e) {
      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

}
