<?php

namespace Drupal\stringoverrides\Controllers;

use Drupal\Core\Controller\ControllerBase;

/**
 * Redirects to the string overrides form for the default language.
 *
 * @package Drupal\stringoverrides\Controllers
 */
class GoToDefaultLanguage extends ControllerBase {

  /**
   * Redirect user to admin form for default language.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Symfony redirect.
   */
  public function go() {
    $language = $this->languageManager()->getDefaultLanguage();
    return $this->redirect('stringoverrides.translations_form', ['language' => $language->getId()]);
  }

}
