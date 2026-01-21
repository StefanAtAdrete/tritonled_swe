<?php

namespace Drupal\commerce_vado\Plugin\Commerce\VadoGroupWidget;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for vado group widgets.
 */
abstract class VadoGroupWidgetBase extends PluginBase implements VadoGroupWidgetInterface, ContainerFactoryPluginInterface {

  /**
   * The group the plugin instance belongs to.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupInterface
   */
  protected $parentEntity;

  /**
   * The group the plugin instance belongs to.
   *
   * @var \Drupal\commerce_vado\Entity\VadoGroupInterface
   */
  protected $selectedVariation;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a VadoGroupWidgetBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Configuration Factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, ModuleHandlerInterface $module_handler, RendererInterface $renderer, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->setConfiguration($configuration);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(EntityInterface $parent_entity) {
    $this->parentEntity = $parent_entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectedVariation(ProductVariationInterface $selected_variation) {
    $this->selectedVariation = $selected_variation;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function allowsMultiple() {
    return $this->pluginDefinition['allows_multiple'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    $has_default_items = $this->parentEntity->hasDefaultItems();
    $default_items = $this->parentEntity->getDefaultItems();
    // multi-value elements expect an array of ids or an empty array.
    if ($this->allowsMultiple()) {
      return $has_default_items ? EntityHelper::extractIds($default_items) : [];
    }
    // Single value elements expect a single id or NULL.
    else {
      return $has_default_items ? reset($default_items)->id() : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'group_title_display' => $this->getDefaultGroupTitleDisplayOption(),
      'group_item_title_renderer' => 'default',
      'group_item_view' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $triggering_element_name = static::getTriggeringElementName($form, $form_state);

    $wrapper_id = 'group-widget-' . $this->getPluginId() . '-ajax';
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['group_title_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Group title display'),
      '#options' => $this->buildGroupTitleDisplayOptions(),
      '#default_value' => $this->configuration['group_title_display'],
      '#required' => TRUE,
    ];

    $form['group_item_title_renderer'] = [
      '#type' => 'select',
      '#title' => $this->t('Group item title renderer'),
      '#options' => [
        'default' => $this->t('Default'),
        'views' => $this->t('Views'),
      ],
      '#default_value' => $this->configuration['group_item_title_renderer'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'configFormAjaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];

    $user_input = $form_state->getUserInput();
    $group_title_renderer = NestedArray::getValue($user_input, array_merge($form['#parents'], ['group_item_title_renderer']));
    if (!$group_title_renderer) {
      $group_title_renderer = $this->configuration['group_item_title_renderer'];
    }

    if ($group_title_renderer == 'views') {
      $item_displays = Views::getApplicableViews('commerce_vado_group_item_display');
      $group_displays = Views::getApplicableViews('commerce_vado_group_display');
      $displays = array_merge($item_displays, $group_displays);
      $view_storage = $this->entityTypeManager->getStorage('view');

      $display_options = [];
      foreach ($displays as $data) {
        [$view_id, $display_id] = $data;
        $view = $view_storage->load($view_id);
        $display = $view->get('display');
        $display_options[$view_id . ':' . $display_id] = $view_id . ' - ' . $display[$display_id]['display_title'];
      }

      if (!empty($display_options)) {
        $form['group_item_view'] = [
          '#type' => 'select',
          '#title' => $this->t('View'),
          '#options' => $display_options,
          '#default_value' => $this->configuration['group_item_view'],
          '#required' => TRUE,
        ];
      }
      else {
        if ($this->currentUser->hasPermission('administer views') && $this->moduleHandler->moduleExists('views_ui')) {
          $form['group_item_view']['no_view_help'] = [
            '#markup' => '<p>' . $this->t('No eligible views were found. <a href=":create">Create a view</a> with a <em>Vado Group Item</em> display, or add such a display to an <a href=":existing">existing view</a>.', [
              ':create' => Url::fromRoute('views_ui.add')->toString(),
              ':existing' => Url::fromRoute('entity.view.collection')->toString(),
            ]) . '</p>',
          ];
        }
        else {
          $form['group_item_view']['no_view_help']['#markup'] = '<p>' . $this->t('No eligible views were found.') . '</p>';
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->setConfiguration($values);
    }
  }

  /**
   * Ajax callback for configuration form.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function configFormAjaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
  }

  /**
   * Determines the name of the triggering element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   *
   * @return string
   *   The name of the triggering element, if the triggering element is
   *   a part of the form.
   */
  protected static function getTriggeringElementName(array $form, FormStateInterface $form_state) {
    $triggering_element_name = '';
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      $parents = array_slice($triggering_element['#parents'], 0, count($form['#parents']));
      if ($form['#parents'] === $parents) {
        $triggering_element_name = end($triggering_element['#parents']);
      }
    }

    return $triggering_element_name;
  }

  /**
   * Gets the default group title display option.
   *
   * @return string
   *   The default group title display option.
   */
  protected function getDefaultGroupTitleDisplayOption() {
    return 'before';
  }

  /**
   * Builds the group title display options.
   *
   * @return array
   *   The group title display options array.
   */
  protected function buildGroupTitleDisplayOptions() {
    return [
      'before' => $this->t('Visible'),
      'invisible' => $this->t('Invisible'),
    ];
  }

  /**
   * Show empty options.
   *
   * @return bool
   *   Whether or not to show empty options.
   */
  protected function showEmptyOption() {
    return !$this->parentEntity->hasDefaultItems() || !$this->parentEntity->isRequired();
  }

  /**
   * Should build empty options.
   *
   * @return bool
   *   Whether or not to build empty options.
   */
  protected function shouldBuildEmptyOption() {
    return $this->showEmptyOption();
  }

  /**
   * Build empty options.
   *
   * @return array
   *   The build empty options array.
   */
  protected function buildEmptyOption() {
    return ['' => $this->t('- None -')];
  }

  /**
   * Build group item options.
   *
   * @return array
   *   The build group item options array.
   */
  protected function buildGroupItemOptions() {
    switch ($this->configuration['group_item_title_renderer']) {
      case 'default':
        return $this->groupItemOptionsDefault();

      case 'views':
        return $this->groupItemOptionsViews();

      default:
        return [];
    }
  }

  /**
   * Builds the default group item options.
   *
   * @return array
   *   The default group item options.
   */
  protected function groupItemOptionsDefault() {
    $addon_group_options = [];
    if ($this->shouldBuildEmptyOption()) {
      $addon_group_options += $this->buildEmptyOption();
    }
    $allow_unpublished = $this->allowsUnpublishedVariations();
    foreach ($this->parentEntity->getItems() as $group_item) {
      // If the group item doesn't have a variation entity,
      // or if the settings don't allow unpublished variations, continue.
      if (!$group_item->getVariation() || !$allow_unpublished && !$group_item->getVariation()->isPublished()) {
        continue;
      }
      $addon_group_options[$group_item->id()] = $group_item->label();
    }

    return $addon_group_options;
  }

  /**
   * Builds the group item options for the views title renderer.
   *
   * @return array
   *   The group item options.
   */
  protected function groupItemOptionsViews() {
    $addon_group_options = [];
    [$view_name, $display_name] = explode(':', $this->configuration['group_item_view']);

    $view = Views::getView($view_name);
    if ($view) {
      $view->setDisplay($display_name);
      $display = $view->displayHandlers->get($display_name);
      // Tell the view what group is being rendered.
      $display->setOption('commerce_vado_group', $this->parentEntity->id());
      // Tell the view which parent variation is selected.
      $display->setOption('selected_variation', $this->selectedVariation);
      $results = $view->executeDisplay($display_name, [$this->parentEntity->id()]);
      $addon_group_options = $this->stripAdminAndAnchorTagsFromResults($results);
    }

    return $addon_group_options;
  }

  /**
   * Sanitizes the results returned by the group item title renderer view.
   *
   * @param array $results
   *   The view results.
   *
   * @return array
   *   The sanitized results.
   */
  protected function stripAdminAndAnchorTagsFromResults(array $results) {
    $allowed_tags = Xss::getAdminTagList();
    if (($key = array_search('a', $allowed_tags)) !== FALSE) {
      unset($allowed_tags[$key]);
    }

    $stripped_results = [];
    foreach ($results as $id => $row) {
      $stripped_results[$id] = ViewsRenderPipelineMarkup::create(
        Xss::filter($this->renderer->renderInIsolation($row), $allowed_tags)
      );
    }

    return $stripped_results;
  }

  /**
   * Checks if the site allows unpublished variations.
   *
   * @return bool
   *   TRUE if the site allows unpublished variations, FALSE otherwise.
   */
  protected function allowsUnpublishedVariations() {
    // Get the setting for how to handle unpublished variations.
    $allow_unpublished = $this->configFactory->get('commerce_vado.settings')->get('allow_unpublished_variations');
    // If the setting is off, return FALSE, else return TRUE.
    if (!$allow_unpublished) {
      return FALSE;
    }
    return TRUE;
  }

}
