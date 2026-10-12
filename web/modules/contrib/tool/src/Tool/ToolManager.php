<?php

declare(strict_types=1);

namespace Drupal\tool\Tool;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\tool\Attribute\Tool;

/**
 * Tool plugin manager.
 */
final class ToolManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/tool/Tool', $namespaces, $module_handler, ToolInterface::class, Tool::class);
    $this->alterInfo('tool_info');
    $this->setCacheBackend($cache_backend, 'tool_plugins');
  }

  /**
   * Creates a tool plugin instance with an optional invoker.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   * @param string|\Drupal\tool\Tool\Invoker|null $invoker
   *   (optional) The invoker identifier, or an Invoker that also declares
   *   what the caller needs from Tool API. Used to contextually alter tool
   *   inputs/outputs via events. If provided, it will be set on the tool
   *   instance after creation.
   *
   * @return \Drupal\tool\Tool\ToolInterface
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = [], string|Invoker|null $invoker = NULL): ToolInterface {
    $instance = parent::createInstance($plugin_id, $configuration);
    assert($instance instanceof ToolInterface);
    if ($invoker !== NULL) {
      $instance->setInvoker($invoker);
    }
    return $instance;
  }

  /**
   * Checks the permission a tool definition declares against an account.
   *
   * This is the static half of tool access. It answers "may this account use
   * this tool at all" from the definition alone, without instantiating the
   * plugin, so catalogs, pickers and advertisement listings can filter the
   * result of getDefinitions() cheaply, with a result they can cache. The
   * instance access() method remains the execution-time gate, and runs this
   * same check first.
   *
   * An allowed result only means the declaration does not rule the account
   * out: the tool's own access check still decides, and a tool that declares
   * no permission is allowed here for every account.
   *
   * The declared permission is parsed like a route _permission requirement
   * in \Drupal\user\Access\PermissionAccessCheck, except that spaces around
   * each name are trimmed: a string that splits on "," requires every part,
   * otherwise its parts split on "+" and any one of them is enough. The two
   * operators do not combine, on routes or here; ::processDefinition()
   * rejects a definition that mixes them.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   The tool definition whose declared permission is checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed when the definition declares no permission, or when the
   *   account satisfies the permission it declares.
   *
   * @see \Drupal\tool\Tool\ToolBase::access()
   */
  public static function checkPermission(ToolDefinition $definition, AccountInterface $account): AccessResultInterface {
    $permission = $definition->getPermission();
    if ($permission === NULL) {
      // Allowed, not neutral: access() combines this result with the tool's
      // own verdict through andIf(), and neutral()->andIf(allowed()) is
      // neutral, so a neutral here would deny every tool that declares no
      // permission.
      $access = AccessResult::allowed();
    }
    else {
      $split = explode(',', $permission);
      $access = count($split) > 1
        ? AccessResult::allowedIfHasPermissions($account, array_map('trim', $split), 'AND')
        : AccessResult::allowedIfHasPermissions($account, array_map('trim', explode('+', $permission)), 'OR');
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id): void {
    /** @var \Drupal\tool\Tool\ToolDefinition $definition */
    parent::processDefinition($definition, $plugin_id);

    if ($definition instanceof ToolDefinition) {
      $permission = $definition->getPermission();
      if ($permission !== NULL && str_contains($permission, ',') && str_contains($permission, '+')) {
        // A mixed string can never be right: the comma-first parse treats
        // each "+" part as one literal permission name, which matches
        // nothing, so ordinary accounts are denied while user 1 and
        // administrators pass. Fail when the definition cache rebuilds, not
        // silently on the account a developer does not test with.
        throw new InvalidPluginDefinitionException($plugin_id, sprintf(
          'The "%s" tool declares the permission "%s", which mixes "," and "+". A route-style permission string uses one operator.',
          $plugin_id,
          $permission,
        ));
      }
      // A tool with no declared permission and no access override can only
      // ever answer neutral, denying every account, administrators
      // included, with nothing telling the author why. When checkAccess()
      // was abstract this failed loudly as a PHP fatal; fail as loudly
      // here, once, when the definition cache rebuilds.
      if ($permission === NULL
        && is_subclass_of($definition->getClass(), ToolBase::class)
        && (new \ReflectionMethod($definition->getClass(), 'checkAccess'))->getDeclaringClass()->getName() === ToolBase::class
        && (new \ReflectionMethod($definition->getClass(), 'access'))->getDeclaringClass()->getName() === ToolBase::class) {
        throw new InvalidPluginDefinitionException($plugin_id, sprintf(
          'The "%s" tool declares no permission and does not override checkAccess() or access().',
          $plugin_id,
        ));
      }
      $forms = $definition->getForms();
      if (!isset($forms['configure'])) {
        $definition->addForm('configure', 'Drupal\tool\Form\ConfigureToolPluginForm');
      }
      if (!isset($forms['execute'])) {
        $definition->addForm('execute', 'Drupal\tool\Form\ExecuteToolPluginForm');
      }
    }
  }

}
