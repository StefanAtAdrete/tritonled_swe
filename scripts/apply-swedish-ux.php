<?php

/**
 * Applies the source-controlled Swedish UX configuration to the active site.
 *
 * Run intentionally with: drush php:script scripts/apply-swedish-ux.php
 */

use Drupal\locale\Gettext;

$language_manager = \Drupal::languageManager();

$overrides = [
  'webform.settings' => [
    'settings.default_form_required_label' => 'Obligatoriskt fält',
    'settings.default_confirmation_back_label' => 'Tillbaka till formuläret',
  ],
  'commerce_checkout.commerce_checkout_flow.quote_request' => [
    'label' => 'Offertförfrågan',
    'configuration.panes.order_fields:checkout.display_label' => 'Offertuppgifter',
    'configuration.panes.completion_message.message.value' => "Ditt offertnummer är [commerce_order:order_number].\r\nDu kan se offerten på din kontosida när du är inloggad.\r\nVi kontaktar dig så snart som möjligt. Tack för din förfrågan.",
    'configuration.panes.order_fields:order_details.display_label' => 'Offertuppgifter',
  ],
  'field.field.commerce_order.quote.field_company_name' => ['label' => 'Företagsnamn'],
  'field.field.commerce_order.quote.field_contact_person' => ['label' => 'Kontaktperson'],
  'field.field.commerce_order.quote.field_desired_delivery_date' => ['label' => 'Önskat leveransdatum'],
  'field.field.commerce_order.quote.field_project_description' => ['label' => 'Projektbeskrivning'],
  'field.field.commerce_order.quote.field_phone' => ['label' => 'Telefon'],
  'commerce_order.commerce_order_type.quote' => ['label' => 'Offert'],
];

foreach ($overrides as $config_name => $values) {
  $override = $language_manager->getLanguageConfigOverride('sv', $config_name);
  foreach ($values as $key => $value) {
    $override->set($key, $value);
  }
  $override->save();
}

foreach (\Drupal::service('config.storage')->listAll('field.field.commerce_product.') as $config_name) {
  if (str_ends_with($config_name, '.body')) {
    $label = $config_name === 'field.field.commerce_product.default.body' ? 'Brödtext' : 'Innehåll';
    $language_manager->getLanguageConfigOverride('sv', $config_name)
      ->set('label', $label)
      ->save();
  }
}

$block = \Drupal::configFactory()->getEditable('block.block.tritonled_radix_mainnavigation');
$block->set('settings.label_display', '0')->save();

$hide_field_labels = function (array &$node) use (&$hide_field_labels): int {
  $changes = 0;
  if (isset($node['id']) && preg_match('/:(body|variations)$/', $node['id'])) {
    if (($node['formatter']['label'] ?? NULL) === 'above') {
      $node['formatter']['label'] = 'hidden';
      $changes++;
    }
  }
  foreach ($node as &$value) {
    if (is_array($value)) {
      $changes += $hide_field_labels($value);
    }
  }
  return $changes;
};

$config_changes = 0;
$storage = \Drupal::service('config.storage');
foreach ($storage->listAll('core.entity_view_display.commerce_product.') as $config_name) {
  $editable = \Drupal::configFactory()->getEditable($config_name);
  $data = $editable->getRawData();
  $changes = $hide_field_labels($data);
  if ($changes) {
    $editable->setData($data)->save();
    $config_changes += $changes;
  }
}

$entity_changes = 0;
$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
foreach ($product_storage->loadMultiple() as $product) {
  if (!$product->hasField('layout_builder__layout') || $product->get('layout_builder__layout')->isEmpty()) {
    continue;
  }
  $changed = FALSE;
  foreach ($product->get('layout_builder__layout')->getSections() as $section) {
    foreach ($section->getComponents() as $component) {
      $configuration = $component->get('configuration');
      if (isset($configuration['id']) && preg_match('/:(body|variations)$/', $configuration['id']) && ($configuration['formatter']['label'] ?? NULL) === 'above') {
        $configuration['formatter']['label'] = 'hidden';
        $component->setConfiguration($configuration);
        $changed = TRUE;
        $entity_changes++;
      }
    }
  }
  if ($changed) {
    $product->save();
  }
}

if (\Drupal::moduleHandler()->moduleExists('locale')) {
  $file = (object) [
    'langcode' => 'sv',
    'uri' => DRUPAL_ROOT . '/modules/custom/tritonled_configurator/translations/sv.po',
  ];
  $translation_report = Gettext::fileToDatabase($file, [
    'overwrite_options' => [
      'not_customized' => TRUE,
      'customized' => TRUE,
    ],
    'customized' => LOCALE_CUSTOMIZED,
  ]);
}

print "Swedish overrides updated: " . count($overrides) . "\n";
print "Template labels hidden: {$config_changes}\n";
print "Product override labels hidden: {$entity_changes}\n";
print 'Translations: ' . json_encode($translation_report ?? []) . "\n";
