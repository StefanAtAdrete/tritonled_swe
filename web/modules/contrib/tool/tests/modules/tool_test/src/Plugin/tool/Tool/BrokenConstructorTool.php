<?php

declare(strict_types=1);

namespace Drupal\tool_test\Plugin\tool\Tool;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tool\Attribute\Tool;
use Drupal\tool\ExecutableResult;
use Drupal\tool\Tool\ToolBase;
use Drupal\tool\Tool\ToolOperation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A tool that cannot be instantiated, used for testing.
 *
 * Stands in for a plugin whose provider module has a broken dependency, so
 * that listing commands can prove one bad plugin does not hide the rest.
 */
#[Tool(
  id: 'broken_constructor_tool',
  label: new TranslatableMarkup('Broken Constructor Tool'),
  description: new TranslatableMarkup('A test tool whose create() always throws.'),
  operation: ToolOperation::Read,
)]
final class BrokenConstructorTool extends ToolBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    throw new \RuntimeException('Service "tool_test.missing" not found.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(array $values): ExecutableResult {
    return ExecutableResult::success(new TranslatableMarkup('Done.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(array $values, AccountInterface $account, bool $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
