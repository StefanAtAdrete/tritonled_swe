<?php

namespace Drupal\commerce_vado\Plugin\views\field;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display vado group item discount amount.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_item_discount_amount")
 */
class GroupItemDiscountAmount extends FieldPluginBase {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a GroupItemDiscountValue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Configuration Factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrencyFormatterInterface $currency_formatter, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currencyFormatter = $currency_formatter;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.currency_formatter'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritDoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['strip_trailing_zeroes']['default'] = FALSE;
    $options['currency_display']['default'] = 'symbol';

    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['strip_trailing_zeroes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing zeroes after the decimal point.'),
      '#default_value' => $this->options['strip_trailing_zeroes'],
    ];

    $form['currency_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Currency display'),
      '#options' => [
        'symbol' => $this->t('Symbol (e.g. "$")'),
        'code' => $this->t('Currency code (e.g. "USD")'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->options['currency_display'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function render(ResultRow $values) {

    // The product variation that was selected in the cart.
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $parent_variation */
    $parent_variation = $this->displayHandler->getOption('selected_variation');
    if (!$parent_variation) {
      return;
    }

    // The current group item that is being rendered by the view.
    /** @var \Drupal\commerce_vado\Entity\VadoGroupItemInterface $group_item */
    $group_item = $values->_entity;
    // If the group item doesn't have a variation entity,
    // or if the settings don't allow unpublished variations, return.
    $allow_unpublished = $this->configFactory->get('commerce_vado.settings')->get('allow_unpublished_variations');
    if (!$group_item->getVariation() || !$allow_unpublished && !$group_item->getVariation()->isPublished()) {
      return;
    }
    $group = $group_item->getGroup();
    $group_item_variation = $group_item->getVariation();

    $bundle_discount_integer = 0;
    if ($parent_variation->hasField('bundle_discount') && !$parent_variation->get('bundle_discount')->isEmpty()) {
      $bundle_discount_integer = $parent_variation->get('bundle_discount')->value;
    }
    $bundle_discount_value = $bundle_discount_integer / 100;

    $group_discount = $bundle_discount_value;
    if ($group->hasGroupDiscount()) {
      $group_discount = $group->getGroupDiscountAmountMultiplier();
    }

    // Use the group_item_discount on the group item if available.
    // If not, use the group_discount.
    $group_item_discount = $group_discount;
    if ($group_item->hasGroupItemDiscount()) {
      $group_item_discount = $group_item->getGroupItemDiscountAmountMultiplier();
    }
    $group_item_discount_value = $group_item_variation->getPrice()->multiply($group_item_discount);

    return $this->currencyFormatter->format(
      $group_item_discount_value->getNumber(),
      $group_item_discount_value->getCurrencyCode(),
      $this->getFormattingOptions()
    );
  }

  /**
   * Gets the formatting options for the currency formatter.
   *
   * @return array
   *   The formatting options.
   */
  protected function getFormattingOptions() {
    $options = [
      'currency_display' => $this->options['currency_display'],
    ];
    if ($this->options['strip_trailing_zeroes']) {
      $options['minimum_fraction_digits'] = 0;
    }

    return $options;
  }

}
