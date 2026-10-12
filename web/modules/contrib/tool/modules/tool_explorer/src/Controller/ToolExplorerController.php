<?php

declare(strict_types=1);

namespace Drupal\tool_explorer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\Adapter\TypedDataAdapterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Serialization\Yaml;
use Drupal\tool\TypedData\EffectiveDataDefinition;

/**
 * Controller for Tool Explorer pages.
 */
class ToolExplorerController extends ControllerBase {

  use TypedDataAdapterTrait;

  /**
   * Constructs a ToolExplorerController object.
   *
   * @param \Drupal\tool\Tool\ToolManager $toolManager
   *   The tool plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   */
  public function __construct(
    protected ToolManager $toolManager,
    protected RendererInterface $renderer,
    protected ModuleExtensionList $moduleExtensionList,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.tool'),
      $container->get('renderer'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * Builds the render array for a tool's icon reference.
   *
   * A tool that declares no icon of its own falls back to the operation
   * icon from the module's own icon pack, so every row carries a meaningful
   * visual. The fallback is presentation-only: it never appears on the
   * definition, so other consumers (e.g. the MCP bridge) see NULL and apply
   * their own defaults.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition.
   *
   * @return array
   *   An icon render array, or an empty array when the core Icon API is
   *   unavailable (<11.1). The icon element itself renders nothing for a
   *   reference that does not resolve.
   */
  protected function buildIcon($definition): array {
    if (!class_exists(IconDefinition::class)) {
      return [];
    }
    $reference = $definition->getIcon() ?? 'tool:operation-' . $definition->getOperation()->value;
    return IconDefinition::getRenderable($reference) ?? [];
  }

  /**
   * Lists all available tools.
   *
   * @return array
   *   A render array.
   */
  public function listTools(): array {
    $definitions = $this->toolManager->getDefinitions();

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $links = [
        'view' => [
          'title' => $this->t('View'),
          'url' => Url::fromRoute('tool_explorer.view', ['plugin_id' => $plugin_id]),
        ],
      ];
      if ($this->currentUser()->hasPermission('administer tool')) {
        $links['execute'] = [
          'title' => $this->t('Execute'),
          'url' => Url::fromRoute('tool_explorer.execute', ['plugin_id' => $plugin_id]),
        ];
      }
      $operations = [
        '#type' => 'dropbutton',
        '#links' => $links,
      ];

      $icon = $this->buildIcon($definition);
      $rows[] = [
        'icon' => $icon ? $this->renderer->render($icon) : '',
        'label' => $definition->getLabel(),
        'id' => $plugin_id,
        'description' => $definition->getDescription(),
        'provider' => $definition->getProvider(),
        'operations' => $this->renderer->render($operations),
      ];
    }

    $build['tools_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Icon'),
        $this->t('Label'),

        $this->t('ID'),
        $this->t('Description'),
        $this->t('Provider'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No tools available.'),
      '#cache' => [
        'tags' => ['tool_list'],
        'contexts' => ['user.permissions'],
      ],
    ];

    return $build;
  }

  /**
   * Displays details for a specific tool.
   *
   * @param string $plugin_id
   *   The plugin ID of the tool.
   *
   * @return array
   *   A render array.
   */
  public function viewTool(string $plugin_id): array {
    if (!$this->toolManager->hasDefinition($plugin_id)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\tool\Tool\ToolDefinition $definition */
    $definition = $this->toolManager->getDefinition($plugin_id);

    $build['tool_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Tool Information'),
      '#open' => TRUE,
    ];

    $build['tool_details']['info'] = [
      '#type' => 'table',
      '#rows' => [
        [
          ['data' => $this->t('Plugin ID'), 'header' => TRUE],
          $plugin_id,
        ],
        [
          ['data' => $this->t('Label'), 'header' => TRUE],
          $definition->getLabel(),
        ],
        [
          ['data' => $this->t('Description'), 'header' => TRUE],
          $definition->getDescription(),
        ],
        [
          ['data' => $this->t('Class'), 'header' => TRUE],
          $definition->getClass(),
        ],
        [
          ['data' => $this->t('Permission'), 'header' => TRUE],
          $definition->getPermission() ?? $this->t('None'),
        ],
      ],
    ];
    // @todo Add future support for nested definitions here.
    // Display input definitions.
    if (!empty($definition->getInputDefinitions())) {
      $build['inputs'] = [
        '#type' => 'details',
        '#title' => $this->t('Input Definitions'),
        '#open' => TRUE,
      ];

      $input_rows = [];
      foreach ($definition->getInputDefinitions() as $name => $input_definition) {
        $input_rows[] = [
          $name,
          $input_definition->getLabel() ?? $name,
          $input_definition->getDataType(),
          $input_definition->isRequired() ? $this->t('Yes') : $this->t('No'),
          $input_definition->getDescription() ?? '',
        ];
      }

      $build['inputs']['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Label'),
          $this->t('Type'),
          $this->t('Required'),
          $this->t('Description'),
        ],
        '#rows' => $input_rows,
      ];

      // Add config schema section.
      $provider_module = $definition->getProvider();
      $schema_file_path = $this->moduleExtensionList->getPath($provider_module) . '/config/schema/' . $provider_module . '.schema.yml';
      $schema_exists = file_exists($schema_file_path);
      $definition_exists = FALSE;
      if ($schema_exists) {
        $schema_content = file_get_contents($schema_file_path);
        $schema_key = 'tool.plugin.' . $plugin_id;
        $definition_exists = strpos($schema_content, $schema_key . ':') !== FALSE;
      }
      // Open if schema is missing.
      $build['config_schema'] = [
        '#type' => 'details',
        '#title' => $this->t('Config Schema (@status)', ['@status' => $definition_exists ? $this->t('Exists') : $this->t('Missing')]),
        '#open' => !$definition_exists,
      ];

      if ($schema_exists) {
        if ($definition_exists) {
          // Schema exists - show the actual schema.
          $build['config_schema']['message'] = [
            '#type' => 'item',
            '#markup' => '<div class="messages messages--status">' . $this->t('Schema file exists at: @path', ['@path' => $schema_file_path]) . '</div>',
          ];

          // Parse and display the actual schema.
          $all_schemas = Yaml::decode($schema_content);
          if (isset($all_schemas[$schema_key])) {
            $build['config_schema']['actual'] = [
              '#type' => 'item',
              '#title' => $this->t('Actual Schema'),
              '#markup' => '<pre>' . Yaml::encode([$schema_key => $all_schemas[$schema_key]]) . '</pre>',
            ];
          }
        }
        else {
          // Schema file exists but doesn't contain this tool's schema.
          $build['config_schema']['warning'] = [
            '#type' => 'item',
            '#markup' => '<div class="messages messages--warning">' .
            $this->t('Schema file exists at @path but does not contain a schema for this tool. Add the following schema:', ['@path' => $schema_file_path]) .
            '</div>',
          ];

          $suggested_schema = $this->buildSuggestedConfigSchema($definition->getInputDefinitions());
          $build['config_schema']['suggested'] = [
            '#type' => 'item',
            '#title' => $this->t('Suggested Schema to Add'),
            '#markup' => '<pre>' . Yaml::encode([$schema_key => $suggested_schema]) . '</pre>',
          ];
        }
      }
      else {
        // Schema file doesn't exist at all.
        $build['config_schema']['warning'] = [
          '#type' => 'item',
          '#markup' => '<div class="messages messages--warning">' .
          $this->t('No schema file found. Create @path with the following content:', ['@path' => $schema_file_path]) .
          '</div>',
        ];

        $suggested_schema = $this->buildSuggestedConfigSchema($definition->getInputDefinitions());
        $schema_key = 'tool.plugin.' . $plugin_id;
        $build['config_schema']['suggested'] = [
          '#type' => 'item',
          '#title' => $this->t('Suggested Schema'),
          '#markup' => '<pre>' . Yaml::encode([$schema_key => $suggested_schema]) . '</pre>',
        ];
      }
    }

    // Display output definitions.
    if (!empty($definition->getOutputDefinitions())) {
      $build['outputs'] = [
        '#type' => 'details',
        '#title' => $this->t('Output Definitions'),
        '#open' => TRUE,
      ];

      $output_rows = [];
      foreach ($definition->getOutputDefinitions() as $name => $output_definition) {
        $output_rows[] = [
          $name,
          $output_definition->getLabel() ?? $name,
          $output_definition->getDataType(),
          $output_definition->getDescription() ?? '',
        ];
      }

      $build['outputs']['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Label'),
          $this->t('Type'),
          $this->t('Description'),
        ],
        '#rows' => $output_rows,
      ];
    }

    return $build;
  }

  /**
   * Gets the title for the view page.
   *
   * @param string $plugin_id
   *   The plugin ID of the tool.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The page title.
   */
  public function viewToolTitle(string $plugin_id): TranslatableMarkup|string {
    if (!$this->toolManager->hasDefinition($plugin_id)) {
      return $this->t('View Tool');
    }

    $definition = $this->toolManager->getDefinition($plugin_id);
    return $this->t('Tool: @label (@id)', ['@label' => $definition->getLabel(), '@id' => $plugin_id]);
  }

  /**
   * Gets the title for the execute page.
   *
   * @param string $plugin_id
   *   The plugin ID of the tool.
   *
   * @return string
   *   The page title.
   */
  public function getExecuteTitle(string $plugin_id): TranslatableMarkup|string {
    if (!$this->toolManager->hasDefinition($plugin_id)) {
      return $this->t('Execute Tool');
    }

    $definition = $this->toolManager->getDefinition($plugin_id);
    return $this->t('Execute: @label', ['@label' => $definition->getLabel() ?? $plugin_id]);
  }

  /**
   * Builds a suggested config schema array from input definitions.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface[] $input_definitions
   *   The input definitions.
   *
   * @return array
   *   The suggested config schema array.
   */
  protected function buildSuggestedConfigSchema(array $input_definitions): array {
    $schema = [
      'type' => 'mapping',
      'mapping' => [],
    ];

    foreach ($input_definitions as $name => $input_definition) {
      $data_definition = EffectiveDataDefinition::createFrom($input_definition);
      $adapter = $this->getAdapterInstance($data_definition, $name);
      $schema['mapping'][$name] = $adapter->getSchemaDefinition($data_definition);
    }

    // Add FullyValidatable constraint to the root mapping.
    $schema['constraints']['FullyValidatable'] = [];

    return $schema;
  }

}
