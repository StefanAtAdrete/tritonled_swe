<?php

namespace Drupal\tritonled_commerce_agent\Plugin\AiFunctionCall;

use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create Commerce Product tool.
 */
#[FunctionCall(
  id: 'commerce_agent:create_product',
  function_name: 'commerce_agent_create_product',
  name: 'Create Commerce Product',
  description: 'Create a new Commerce product with title, description, and store',
  group: 'commerce_tools',
  context_definitions: [
    'bundle' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Product bundle'),
      description: new TranslatableMarkup('Product bundle (e.g., luminaire)'),
      required: TRUE,
    ),
    'title' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Product title'),
      description: new TranslatableMarkup('Product title'),
      required: TRUE,
    ),
    'store_id' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Store ID'),
      description: new TranslatableMarkup('Store ID (default: 1 for TritonLed Sverige)'),
      required: FALSE,
    ),
    'description' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Product description'),
      description: new TranslatableMarkup('Product description'),
      required: FALSE,
    ),
    'in_hero' => new ContextDefinition(
      data_type: 'boolean',
      label: new TranslatableMarkup('Show in hero'),
      description: new TranslatableMarkup('Show in hero carousel (field_in_hero)'),
      required: FALSE,
    ),
  ],
)]
class CreateCommerceProduct extends FunctionCallBase {

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
      $store_id = $parameters['store_id'] ?? 1;
      
      $store = $this->entityTypeManager->getStorage('commerce_store')->load($store_id);
      if (!$store) {
        return [
          'success' => FALSE,
          'error' => "Store with ID {$store_id} not found",
        ];
      }

      $values = [
        'type' => $parameters['bundle'],
        'title' => $parameters['title'],
        'stores' => [$store],
      ];

      if (!empty($parameters['description'])) {
        $values['field_product_description'] = [
          'value' => $parameters['description'],
          'format' => 'basic_html',
        ];
      }

      if (isset($parameters['in_hero'])) {
        $values['field_in_hero'] = $parameters['in_hero'];
      }

      $product = Product::create($values);
      $product->save();

      return [
        'success' => TRUE,
        'product_id' => $product->id(),
        'title' => $product->getTitle(),
        'message' => "Product created successfully with ID: {$product->id()}",
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
