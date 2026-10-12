<?php

namespace Drupal\tool\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\TypedData\Adapter\TypedDataAdapterTrait;
use Drupal\tool\TypedData\EffectiveDataDefinition;

/**
 * Form for executing tool plugins.
 */
class ExecuteToolPluginForm extends ToolPluginFormBase implements ContainerInjectionInterface {
  use TypedDataTrait;
  use TypedDataAdapterTrait;

  /**
   * Constructs a ToolExplorerController object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected MessengerInterface $messenger,
    protected AccountProxyInterface $currentUser,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('messenger'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Check the declared permission before building anything for the
    // account, so an unauthorized account gets no widget tree derived from
    // refined definitions (#3583077). access() at submit still gates
    // execution.
    if (!$this->checkDeclaredPermission()->isAllowed()) {
      $form['access_denied'] = [
        '#markup' => $this->t('You do not have access to execute this tool.'),
      ];
      return $form;
    }
    $form['#tree'] = TRUE;
    $values = $this->plugin->getInputValues();
    foreach ($this->plugin->getInputDefinitions() as $name => $definition) {
      $typed_data = $this->getTypedDataManager()->create(EffectiveDataDefinition::createFrom($definition), $values[$name], $name);
      $form[$name] = [];
      $adapter = $this->getAdapterInstance(EffectiveDataDefinition::createFrom($definition), $name);
      $subform_state = SubformState::createForSubform($form[$name], $form, $form_state);
      $form[$name] = $adapter->formElement($typed_data, $form[$name], $subform_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // The build-time check ran for the account that saw the form; this one
    // guards the submission itself, before any submitted value is extracted
    // or transformed (#3583077).
    if (!$this->checkDeclaredPermission()->isAllowed()) {
      $this->messenger->addError($this->t('You do not have access to execute this tool.'));
      return;
    }
    $values = $this->plugin->getInputValues();
    foreach ($this->plugin->getInputDefinitions() as $name => $definition) {
      $adapter = $this->getAdapterInstance(EffectiveDataDefinition::createFrom($definition), $name);
      $typed_data = $this->getTypedDataManager()->create(EffectiveDataDefinition::createFrom($definition), $values[$name], $name);
      $subform_state = SubformState::createForSubform($form[$name], $form, $form_state);
      $adapter->extractFormValues($typed_data, $form[$name], $subform_state);
      $this->plugin->setInputValue($name, $typed_data->getValue());
    }

    if ($this->plugin->access()) {
      $this->plugin->execute();
      $this->messenger->addMessage((string) $this->plugin->getResultMessage());
    }
    else {
      $this->messenger->addError($this->t('You do not have access to execute this tool.'));
    }
  }

  /**
   * Checks the tool's declared permission for the current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The permission verdict; allowed when the tool declares no permission.
   */
  private function checkDeclaredPermission(): AccessResultInterface {
    /** @var \Drupal\tool\Tool\ToolDefinition $definition */
    $definition = $this->plugin->getPluginDefinition();
    return ToolManager::checkPermission($definition, $this->currentUser);
  }

}
