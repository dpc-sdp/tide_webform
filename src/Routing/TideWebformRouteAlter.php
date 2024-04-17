<?php

namespace Drupal\tide_webform\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Overrides the SomeController class.
 */
class TideWebformRouteAlter extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Override the original controller class with your custom controller class.
    if ($route = $collection->get('entity.webform.results_export')) {
      $route->setDefault('_controller', '\Drupal\tide_webform\Controller\TideWebformResultsExportController::index');
    }
  }

}
