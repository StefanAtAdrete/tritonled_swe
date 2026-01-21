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
  id: 'commerce_agent:list_products',
  function_name: 'commerce_agent_list_products',
  name: 'List Commerce Products',
  description: 'List all commerce products with their variations',
  group: 'commerce_tools',
  context_definitions: [
    'bundle' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Bundle'),
      description: new TranslatableMarkup('Product bundle to filter by (optional)'),
      required: FALSE,
    ),
    'limit' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Limit'),
      description: new TranslatableMarkup('Maximum number of products to return (default: 50)'),
      required: FALSE,
    ),
  ],
)]
class ListCommerceProducts extends FunctionCallBase {

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
      $storage = $this->entityTypeManager->getStorage('commerce_product');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC');

      if (!empty($parameters['bundle'])) {
        $query->condition('type', $parameters['bundle']);
      }

      $limit = $parameters['limit'] ?? 50;
      $query->range(0, $limit);

      $ids = $query->execute();

      if (empty($ids)) {
        return [
          'success' => TRUE,
          'products' => [],
          'message' => 'No products found',
        ];
      }

      $products = $storage->loadMultiple($ids);
      $result = [];

      foreach ($products as $product) {
        $variations = [];
        foreach ($product->getVariations() as $variation) {
          $variations[] = [
            'id' => $variation->id(),
            'sku' => $variation->getSku(),
            'title' => $variation->getTitle(),
            'price' => $variation->getPrice() ? $variation->getPrice()->__toString() : NULL,
          ];
        }

        $result[] = [
          'id' => $product->id(),
          'title' => $product->getTitle(),
          'type' => $product->bundle(),
          'variation_count' => count($variations),
          'variations' => $variations,
        ];
      }

      return [
        'success' => TRUE,
        'count' => count($result),
        'products' => $result,
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
