<?php

declare(strict_types=1);

namespace Drupal\tool_test\EventSubscriber;

use Drupal\tool\Event\ToolInputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolInputTransformEvent;
use Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolOutputTransformEvent;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\tool\TypedData\OutputDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber that transforms tool inputs and outputs.
 *
 * This subscriber only acts when the invoker is 'test_invoker'.
 */
final class TestToolTransformSubscriber implements EventSubscriberInterface {

  /**
   * The invoker ID this subscriber responds to.
   */
  const INVOKER_ID = 'test_invoker';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ToolInputTransformEvent::class => 'onInputTransform',
      ToolOutputTransformEvent::class => 'onOutputTransform',
      ToolInputDefinitionNormalizeEvent::class => 'onInputDefinitionNormalize',
      ToolOutputDefinitionNormalizeEvent::class => 'onOutputDefinitionNormalize',
    ];
  }

  /**
   * Transforms input values for the test invoker.
   *
   * Simulates upcasting: if the input name is 'name' and the value looks
   * like an email, resolve it to just the username portion.
   *
   * @param \Drupal\tool\Event\ToolInputTransformEvent $event
   *   The input transform event.
   */
  public function onInputTransform(ToolInputTransformEvent $event): void {
    if ($event->getInvoker()?->id !== self::INVOKER_ID) {
      return;
    }

    if ($event->getInputName() === 'name') {
      $value = $event->getValue();
      // Simulate resolving an email to a username.
      if (is_string($value) && str_contains($value, '@')) {
        $event->setValue(strstr($value, '@', TRUE));
      }
    }
  }

  /**
   * Transforms individual output values for the test invoker.
   *
   * Simulates downcasting: wraps output values in a token format and adds
   * a message explaining how to use the token.
   *
   * @param \Drupal\tool\Event\ToolOutputTransformEvent $event
   *   The output transform event.
   */
  public function onOutputTransform(ToolOutputTransformEvent $event): void {
    if ($event->getInvoker()?->id !== self::INVOKER_ID) {
      return;
    }

    if ($event->getOutputName() === 'message') {
      $token = '[token:' . $event->getValue() . ']';
      $event->setValue($token);
      $event->addMessage('Use ' . $token . ' to reference this value in subsequent calls.');
    }
  }

  /**
   * Transforms input definitions during normalization for the test invoker.
   *
   * Simulates schema transformation: replaces the 'name' input definition
   * to indicate it accepts an email address (since onInputTransform will
   * upcast it).
   *
   * @param \Drupal\tool\Event\ToolInputDefinitionNormalizeEvent $event
   *   The input definition normalize event.
   */
  public function onInputDefinitionNormalize(ToolInputDefinitionNormalizeEvent $event): void {
    if ($event->getInvoker()?->id !== self::INVOKER_ID) {
      return;
    }

    if ($event->getName() === 'name') {
      $event->setDefinition(new InputDefinition(
        data_type: 'string',
        label: new TranslatableMarkup('Email'),
        description: new TranslatableMarkup('The email address of the user. Will be resolved to a username.'),
      ));
    }
  }

  /**
   * Transforms output definitions during normalization for the test invoker.
   *
   * Replaces the 'message' output definition to describe token format.
   *
   * @param \Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent $event
   *   The output definition normalize event.
   */
  public function onOutputDefinitionNormalize(ToolOutputDefinitionNormalizeEvent $event): void {
    if ($event->getInvoker()?->id !== self::INVOKER_ID) {
      return;
    }

    if ($event->getName() === 'message') {
      $event->setDefinition(new OutputDefinition(
        data_type: 'string',
        label: new TranslatableMarkup('Token'),
        description: new TranslatableMarkup('A token reference that can be used in subsequent calls.'),
      ));
    }
  }

}
