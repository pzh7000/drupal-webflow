<?php

namespace Drupal\webflow\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for webflow routes.
 */
class WebflowController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
