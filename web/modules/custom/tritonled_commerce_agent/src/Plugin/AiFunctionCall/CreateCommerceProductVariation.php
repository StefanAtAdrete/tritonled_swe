<?php

namespace Drupal\tritonled_commerce_agent\Plugin\AiFunctionCall;

use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Utility\ContextDefinitionNormalizer;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create Commerce Product Variation tool.
 */
#[FunctionCall(
  id: 'commerce_agent:create_variation',
  function_name: 'commerce_agent_create_variation',
  name: 'Create Product Variation',
  description: 'Create a new product variation with SKU, price, attributes, and technical specs',
  group: 'commerce_tools',
  context_definitions: [
    'bundle' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Variation bundle'),
      description: new TranslatableMarkup('Variation bundle (e.g., luminaire)'),
      required: TRUE,
    ),
    'sku' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('SKU'),
      description: new TranslatableMarkup('SKU code'),
      required: TRUE,
    ),
    'title' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Title'),
      description: new TranslatableMarkup('Variation title'),
      required: TRUE,
    ),
    'price_number' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Price'),
      description: new TranslatableMarkup('Price amount (e.g., "2995")'),
      required: TRUE,
    ),
    'price_currency' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Currency'),
      description: new TranslatableMarkup('Currency code (default: SEK)'),
      required: FALSE,
    ),
    'attribute_watt' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Watt attribute'),
      description: new TranslatableMarkup('Watt attribute value ID'),
      required: FALSE,
    ),
    'attribute_cct' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('CCT attribute'),
      description: new TranslatableMarkup('CCT attribute value ID'),
      required: FALSE,
    ),
    'field_watt' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Watt'),
      description: new TranslatableMarkup('Watt value'),
      required: FALSE,
    ),
    'field_cct' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('CCT'),
      description: new TranslatableMarkup('CCT value (e.g., 4000)'),
      required: FALSE,
    ),
    'field_lumen' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Lumen'),
      description: new TranslatableMarkup('Lumen output'),
      required: FALSE,
    ),
    'field_cri' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('CRI'),
      description: new TranslatableMarkup('CRI value (e.g., ">80")'),
      required: FALSE,
    ),
    'field_height' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Height'),
      description: new TranslatableMarkup('Height in mm'),
      required: FALSE,
    ),
    'field_length' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Length'),
      description: new TranslatableMarkup('Length in mm'),
      required: FALSE,
    ),
    'field_width' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Width'),
      description: new TranslatableMarkup('Width in mm'),
      required: FALSE,
    ),
  ],
)]
class CreateCommerceProductVariation extends FunctionCallBase {

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
      $currency = $parameters['price_currency'] ?? 'SEK';
      
      $values = [
        'type' => $parameters['bundle'],
        'sku' => $parameters['sku'],
        'title' => $parameters['title'],
        'price' => new Price($parameters['price_number'], $currency),
        'field_sku' => $parameters['sku'],
      ];

      if (!empty($parameters['attribute_watt'])) {
        $values['attribute_watt'] = $parameters['attribute_watt'];
      }
      if (!empty($parameters['attribute_cct'])) {
        $values['attribute_cct'] = $parameters['attribute_cct'];
      }

      $field_mapping = [
        'field_watt',
        'field_cct',
        'field_lumen',
        'field_cri',
        'field_height',
        'field_length',
        'field_width',
      ];

      foreach ($field_mapping as $field) {
        if (isset($parameters[$field])) {
          $values[$field] = $parameters[$field];
        }
      }

      $variation = ProductVariation::create($values);
      $variation->save();

      return [
        'success' => TRUE,
        'variation_id' => $variation->id(),
        'sku' => $variation->getSku(),
        'title' => $variation->getTitle(),
        'message' => "Variation created successfully with ID: {$variation->id()}",
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
