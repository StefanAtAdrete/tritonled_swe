<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Drush\Generators\ToolGenerator;
use DrupalCodeGenerator\Application as DcgApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the `drush generate plugin:tool` generator.
 *
 * Drives the generator through a Drupal Code Generator application built from
 * the kernel container (the same way Drush wires it at runtime) and asserts on
 * the files it writes: the plugin plus the unit and kernel test stubs.
 *
 * @group tool
 */
#[CoversClass(ToolGenerator::class)]
#[Group('tool')]
#[RunTestsInSeparateProcesses]
final class ToolGeneratorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'system',
    'user',
  ];

  /**
   * A sandbox directory the generator writes into.
   */
  private string $destination;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->destination = $this->siteDirectory . '/tool_generator_sandbox';
    (new Filesystem())->mkdir($this->destination);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    (new Filesystem())->remove($this->destination);
    parent::tearDown();
  }

  /**
   * Generates a plugin and both mandatory test stubs for a Read tool.
   */
  public function testGeneratesPluginAndTests(): void {
    $display = $this->runGenerator([
      // Module machine name.
      'tool',
      // Tool label.
      'Demo Probe',
      // Plugin ID.
      'tool_demo_probe',
      // Plugin class.
      'DemoProbeTool',
      // Description.
      'Returns a demo probe value.',
      // Operation.
      'Read',
      // Destructive?
      'no',
    ]);

    // The plugin file.
    $plugin = $this->assertGeneratedFile('src/Plugin/tool/Tool/DemoProbeTool.php');
    $this->assertStringContainsString('namespace Drupal\tool\Plugin\tool\Tool;', $plugin);
    $this->assertStringContainsString('final class DemoProbeTool extends ToolBase', $plugin);
    $this->assertStringContainsString("id: 'tool_demo_probe',", $plugin);
    $this->assertStringContainsString('operation: ToolOperation::Read,', $plugin);
    // A non-destructive tool must not declare the destructive flag.
    $this->assertStringNotContainsString('destructive: TRUE,', $plugin);
    // Access must be scaffolded fail-closed: it throws until access is real.
    $this->assertStringContainsString('throw new \LogicException(', $plugin);

    // The mandatory unit test for checkAccess().
    $unit = $this->assertGeneratedFile('tests/src/Unit/Tool/DemoProbeToolAccessTest.php');
    $this->assertStringContainsString('final class DemoProbeToolAccessTest extends UnitTestCase', $unit);
    $this->assertStringContainsString('public function testAccessIsFailClosed()', $unit);

    // The mandatory kernel test for doExecute().
    $kernel = $this->assertGeneratedFile('tests/src/Kernel/Tool/DemoProbeToolTest.php');
    $this->assertStringContainsString('final class DemoProbeToolTest extends KernelTestBase', $kernel);
    $this->assertStringContainsString('#[RunTestsInSeparateProcesses]', $kernel);
    $this->assertStringContainsString("createInstance('tool_demo_probe')", $kernel);

    // The generator reports the three files it wrote.
    $this->assertStringContainsString('DemoProbeTool.php', $display);
  }

  /**
   * A destructive Write tool carries the destructive flag through the template.
   */
  public function testGeneratesDestructiveWriteTool(): void {
    $this->runGenerator([
      'tool',
      'Nuke Thing',
      'tool_nuke_thing',
      'NukeThingTool',
      'Deletes a thing irreversibly.',
      'Write',
      'yes',
    ]);

    $plugin = $this->assertGeneratedFile('src/Plugin/tool/Tool/NukeThingTool.php');
    $this->assertStringContainsString('operation: ToolOperation::Write,', $plugin);
    $this->assertStringContainsString('destructive: TRUE,', $plugin);
  }

  /**
   * Runs the generator with the given interactive answers.
   *
   * @param string[] $answers
   *   The answers fed to the generator's questions, in prompt order.
   *
   * @return string
   *   The command display output.
   */
  private function runGenerator(array $answers): string {
    $application = DcgApplication::create($this->container);
    $command = new ToolGenerator();
    // Bind the command to the DCG application so it inherits the application's
    // helper set and can dispatch its asset events. setApplication() (unlike
    // Application::add()) is not deprecated and needs no Symfony 7.4+.
    $command->setApplication($application);

    $tester = new CommandTester($command);
    $tester->setInputs($answers);
    $status = $tester->execute([
      '--working-dir' => $this->destination,
      '--destination' => $this->destination,
    ]);

    $this->assertSame(0, $status, 'Generator exited successfully. Output: ' . $tester->getDisplay());

    return $tester->getDisplay();
  }

  /**
   * Asserts a file was generated under the destination and returns its content.
   */
  private function assertGeneratedFile(string $relative_path): string {
    $path = $this->destination . '/' . $relative_path;
    $this->assertFileExists($path);
    return (string) file_get_contents($path);
  }

}
