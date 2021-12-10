<?php

namespace Drupal\webflow;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

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

    try {
      $response = $this->httpClient->request('GET', 'https://api.webflow.com/sites', $options);
    } catch (ClientException $e) {
      throw $e;
    }

    return json_decode($response->getBody());

  }

  public function getSiteId() {
    $site_id = \Drupal::state()->get('wf_site_id');
    if (!empty($site_id)) {
      return $site_id;
    }
    try {
      $response = $this->getSites();
    } catch (ClientException $e) {
      // TODO: add better error handling.
      \Drupal::messenger()->addError("The API key you used is invalid: failed to list sites");
    }
    $id = $response[0]->_id;
    \Drupal::state()->set('wf_site_id', $id);
    return $id;

  }

  public function getSiteDomain($site = NULL) {
    if ($site === NULL) {
      $site = $this->getSites();
    }

    $short_name = $site[0]->shortName;

    $this->domain = $short_name . ".webflow.io";

    return $this->domain;

  }

  public function getStaticPages($site_domain = NULL) {
    $options = [
      'headers' => $this->buildHeaders(),
    ];

    if ($site_domain === NULL) {
      $site_domain = $this->getSiteDomain();
    }

    try {
      $response = $this->httpClient->request('GET', "https://" . $site_domain . "/static-manifest.json", $options);
    } catch (ClientException $e) {
      throw $e;
    }

    return json_decode($response->getBody());

  }

  private function handle_client_response() {
    try {
      $client->request('GET', 'https://api.webflow.com/sites');
    } catch (ClientException $e) {
      echo Psr7\Message::toString($e->getRequest());
      echo Psr7\Message::toString($e->getResponse());
    }
  }

}
