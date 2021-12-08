<?php

namespace Drupal\webflow;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * WebflowApi service.
 */
class WebflowApi {

  /**
   * Constructs a WebflowApi object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  private function getClientId() {
    return $this->configFactory->get('webflow.settings')->get('api-key');
  }

  public function buildHeaders() {
    $token = $this->getClientId();
    return [
      'Authorization' => "Bearer $token",
      "accept-version" => "1.0.0",
      "Content-Type" => "application/json; charset=utf-8",
    ];
  }

  /**
   * Method description.
   */
  public function getSites() {
    $options = [
      'headers' => $this->buildHeaders(),
    ];

    $response = $this->httpClient->request('GET', 'https://api.webflow.com/sites', $options);
    return json_decode($response->getBody());
  }

}
