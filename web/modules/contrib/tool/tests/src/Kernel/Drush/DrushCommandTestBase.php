<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolManager;
use Drush\Style\DrushStyle;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base class for Drush command kernel tests.
 */
#[RunTestsInSeparateProcesses]
abstract class DrushCommandTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   *
   * @var \Drupal\tool\Tool\ToolManager
   */
  protected ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // DrushStyle is not autoloaded in the test environment, so we need to
    // require it manually to satisfy the assertion in DrushCommands::io().
    $drushStyleFile = $this->root . '/../vendor/drush/drush/src-symfony-compatibility/v6/Style/DrushStyle.php';
    if (!class_exists(DrushStyle::class) && file_exists($drushStyleFile)) {
      require_once $drushStyleFile;
    }

    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Sets up a Drush command with test I/O.
   *
   * @param object $command
   *   The Drush command instance.
   * @param \Symfony\Component\Console\Input\ArrayInput $input
   *   The input.
   * @param \Symfony\Component\Console\Output\BufferedOutput $output
   *   The output.
   */
  protected function configureCommand(object $command, ArrayInput $input, BufferedOutput $output): void {
    $command->setLogger(new NullLogger());
    $command->restoreState($input, $output, new DrushStyle($input, $output));
  }

}
