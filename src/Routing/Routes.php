<?php


namespace Drupal\webflow\Routing;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\webflow\Controller\EntryPoint;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Routes {

  public function routes() {
    $routes = new RouteCollection();

    // @TODO: DI the config service.
    $mappings = \Drupal::config('webflow.settings')->get('path_mappings');
    foreach ($mappings as $delta => $mapping) {
      $routes->add('webflow'. $delta, static::buildRoute($mapping));
    }

    return $routes;
  }

  protected static function buildRoute(array $mapping) {
    $route = new Route($mapping['drupal_path']);
    $route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => EntryPoint::class . '::index']);
    $route->addDefaults(['webflow_page' => $mapping['webflow_page']]);
    // @TODO: Set up permissions?
    $route->setRequirement('_access', 'TRUE');
    $route->setMethods(['GET']);
    return $route;
  }

}
