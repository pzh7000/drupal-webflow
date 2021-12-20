<?php

namespace Drupal\webflow\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Webflow routes.
 */
class EntryPoint extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   * @TODO: Add http client to DI
   * @TODO: Add webflow API to DI
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Builds the Webflow page response.
   */
  public function index($webflow_page = NULL) {
    $url = $this->buildUrl($webflow_page);
    echo \Drupal::httpClient()
      ->get($url)
      ->getBody();
    // @TODO: Is there a better way to do this?
    // Prevent the rest of Drupal from doing anything.
    die;
  }

  private function buildUrl(string $webflow_page) {
    /** @var \Drupal\webflow\WebflowApi $webflow */
    $webflow = \Drupal::service('webflow.webflow_api');
    $domain = $webflow->getSiteDomain();
    return Url::fromUri('http://' . $domain . '/index.html')->toString();

  }

}
