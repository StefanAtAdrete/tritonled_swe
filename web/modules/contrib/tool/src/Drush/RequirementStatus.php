<?php

declare(strict_types=1);

namespace Drupal\tool\Drush;

use Drupal\tool\Exception\RequirementsException;
use Drupal\tool\Tool\ToolManager;

/**
 * The result of probing a tool's configuration-time requirements.
 *
 * Listing tools means instantiating every plugin, and instantiation runs each
 * plugin's create() against the container. A plugin whose provider has a
 * broken dependency reports as unmet instead of failing the whole listing.
 *
 * @internal
 */
final readonly class RequirementStatus {

  /**
   * Use forTool() to build a status.
   */
  private function __construct(
    public bool $met,
    public ?string $message,
  ) {}

  /**
   * Instantiates the tool and checks its requirements.
   */
  public static function forTool(ToolManager $manager, string $tool_id): self {
    try {
      $manager->createInstance($tool_id, [], DrushInvoker::create())->checkRequirements();
    }
    catch (RequirementsException $e) {
      return new self(FALSE, $e->getMessage());
    }
    catch (\Throwable $e) {
      return new self(FALSE, 'Could not instantiate tool: ' . $e->getMessage());
    }
    return new self(TRUE, NULL);
  }

  /**
   * The status as one cell of a listing.
   */
  public function label(): string {
    return $this->met ? 'Met' : (string) $this->message;
  }

  /**
   * The status as the JSON shape shared by the commands.
   *
   * @return array{met: bool, message: string|null}
   *   The status.
   */
  public function toArray(): array {
    return ['met' => $this->met, 'message' => $this->message];
  }

}
