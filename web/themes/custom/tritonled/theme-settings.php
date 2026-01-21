<?php

/**
 * @file
 * Theme settings for TritonLED theme.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function tritonled_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  
  // TritonLED Theme Settings
  $form['tritonled_settings'] = [
    '#type' => 'details',
    '#title' => t('TritonLED Settings'),
    '#open' => TRUE,
  ];

  // Color Scheme
  $form['tritonled_settings']['color_scheme'] = [
    '#type' => 'details',
    '#title' => t('Color Scheme'),
    '#open' => FALSE,
  ];

  $form['tritonled_settings']['color_scheme']['tritonled_primary_color'] = [
    '#type' => 'color',
    '#title' => t('Primary Color'),
    '#default_value' => theme_get_setting('tritonled_primary_color') ?? '#0066cc',
    '#description' => t('Primary brand color used throughout the site.'),
  ];

  $form['tritonled_settings']['color_scheme']['tritonled_accent_color'] = [
    '#type' => 'color',
    '#title' => t('Accent Color'),
    '#default_value' => theme_get_setting('tritonled_accent_color') ?? '#00cc66',
    '#description' => t('Accent color for highlights and CTAs.'),
  ];

  // Layout Settings
  $form['tritonled_settings']['layout'] = [
    '#type' => 'details',
    '#title' => t('Layout Settings'),
    '#open' => FALSE,
  ];

  $form['tritonled_settings']['layout']['tritonled_container_width'] = [
    '#type' => 'select',
    '#title' => t('Container Width'),
    '#options' => [
      'container' => t('Fixed Width (Bootstrap default)'),
      'container-fluid' => t('Full Width'),
      'container-lg' => t('Large Container'),
      'container-xl' => t('Extra Large Container'),
    ],
    '#default_value' => theme_get_setting('tritonled_container_width') ?? 'container',
    '#description' => t('Choose the maximum width for the site content.'),
  ];

  // Product Display Settings
  $form['tritonled_settings']['products'] = [
    '#type' => 'details',
    '#title' => t('Product Display'),
    '#open' => FALSE,
  ];

  $form['tritonled_settings']['products']['tritonled_show_specs_default'] = [
    '#type' => 'checkbox',
    '#title' => t('Show specifications by default'),
    '#default_value' => theme_get_setting('tritonled_show_specs_default') ?? TRUE,
    '#description' => t('Display product specifications table on product pages by default.'),
  ];

  $form['tritonled_settings']['products']['tritonled_enable_quick_view'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable quick view'),
    '#default_value' => theme_get_setting('tritonled_enable_quick_view') ?? FALSE,
    '#description' => t('Enable quick view modal for products in listings.'),
  ];

  // Performance Settings
  $form['tritonled_settings']['performance'] = [
    '#type' => 'details',
    '#title' => t('Performance'),
    '#open' => FALSE,
  ];

  $form['tritonled_settings']['performance']['tritonled_lazy_load_images'] = [
    '#type' => 'checkbox',
    '#title' => t('Lazy load images'),
    '#default_value' => theme_get_setting('tritonled_lazy_load_images') ?? TRUE,
    '#description' => t('Enable lazy loading for product images.'),
  ];
}
