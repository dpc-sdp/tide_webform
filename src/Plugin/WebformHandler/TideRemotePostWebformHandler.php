<?php

namespace Drupal\tide_webform\Plugin\WebformHandler;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;

/**
 * Tide Webform submission remote post handler.
 *
 * @WebformHandler(
 *   id = "tide_remote_post",
 *   label = @Translation("Tide remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL with a chance to modify it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class TideRemotePostWebformHandler extends RemotePostWebformHandler {

  /**
   * {@inheritdoc}
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    $data = parent::getRequestData($state, $webform_submission);
    \Drupal::moduleHandler()->alter('tide_webform_post', $data, $this);
    return $data;
  }

}
