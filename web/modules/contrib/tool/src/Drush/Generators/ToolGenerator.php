<?php

declare(strict_types=1);

namespace Drupal\tool\Drush\Generators;

use Drupal\tool\Tool\ToolOperation;
use DrupalCodeGenerator\Asset\AssetCollection as Assets;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;
use DrupalCodeGenerator\Validator\Required;

/**
 * Generates a Tool plugin.
 *
 * The "plugin:" name prefix groups it under the plugin section of
 * `drush generate`, next to plugin:action, plugin:block, etc.
 *
 * Run with `drush generate plugin:tool` (alias `drush generate tool`).
 */
#[Generator(
  name: 'plugin:tool',
  description: 'Generates a Tool plugin.',
  aliases: ['tool'],
  templatePath: __DIR__,
  type: GeneratorType::MODULE_COMPONENT,
)]
final class ToolGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, Assets $assets): void {
    $ir = $this->createInterviewer($vars);

    $vars['machine_name'] = $ir->askMachineName();
    $vars['plugin_label'] = $ir->askPluginLabel('Tool label');
    $vars['plugin_id'] = $ir->askPluginId();
    $vars['class'] = $ir->askPluginClass(suffix: 'Tool');
    // No default: an accepted default would end up verbatim in the generated
    // #[Tool] description, which agents rely on to pick the tool.
    $vars['description'] = $ir->ask('Tool description', NULL, new Required());

    $operations = [];
    foreach (ToolOperation::cases() as $case) {
      $operations[$case->name] = $case->name;
    }
    $vars['operation'] = $ir->choice('Operation', $operations, ToolOperation::Read->name);

    $vars['destructive'] = $ir->confirm('Is the tool destructive (irreversible write/trigger)?', FALSE);

    $assets->addFile('src/Plugin/tool/Tool/{class}.php', 'tool.twig');

    $assets->addFile('tests/src/Unit/Tool/{class}AccessTest.php', 'tool-test-unit.twig');
    $assets->addFile('tests/src/Kernel/Tool/{class}Test.php', 'tool-test-kernel.twig');
  }

}
