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
  id: 'commerce_agent:add_variation_to_product',
  function_name: 'commerce_agent_add_variation_to_product',
  name: 'Add Variation to Product',
  description: 'Add one or more variations to a product',
  group: 'commerce_tools',
  context_definitions: [
    'product_id' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Product ID'),
      description: new TranslatableMarkup('Product ID'),
      required: TRUE,
    ),
    'variation_ids' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Variation IDs'),
      description: new TranslatableMarkup('Comma-separated variation IDs to add'),
      required: TRUE,
    ),
  ],
)]
class AddVariationToProduct extends FunctionCallBase {

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
      $product = $this->entityTypeManager
        ->getStorage('commerce_product')
        ->load($parameters['product_id']);

      if (!$product) {
        return [
          'success' => FALSE,
          'error' => "Product with ID {$parameters['product_id']} not found",
        ];
      }

      $variation_ids = is_array($parameters['variation_ids']) 
        ? $parameters['variation_ids'] 
        : array_map('trim', explode(',', $parameters['variation_ids']));

      $current_variations = $product->getVariationIds();
      $all_variation_ids = array_merge($current_variations, $variation_ids);
      $product->setVariationIds($all_variation_ids);
      $product->save();

      return [
        'success' => TRUE,
        'product_id' => $product->id(),
        'variation_count' => count($all_variation_ids),
        'message' => "Added " . count($variation_ids) . " variation(s) to product {$product->id()}",
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
