<?php

namespace Drupal\tide_webform\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Controller\WebformResultsExportController;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for webform submission export.
 */
class TideWebformResultsExportController extends WebformResultsExportController {

  /* ************************************************************************ */
  // Batch functions.
  // Using static method to prevent the service container from being serialized.
  // Prevents 'AssertionError: The container was serialized.' exception.
  /* ************************************************************************ */

  /**
   * Batch API; Initialize batch operations.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform source entity.
   * @param array $export_options
   *   An array of export options.
   *
   * @see http://www.jeffgeerling.com/blogs/jeff-geerling/using-batch-api-build-huge-csv
   */
  public static function batchSet(WebformInterface $webform, EntityInterface $source_entity = NULL, array $export_options) {
    if (!empty($export_options['excluded_columns']) && is_string($export_options['excluded_columns'])) {
      $excluded_columns = explode(',', $export_options['excluded_columns']);
      $export_options['excluded_columns'] = array_combine($excluded_columns, $excluded_columns);
    }

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    $parameters = [
      $webform,
      $source_entity,
      $export_options,
    ];
    $batch = [
      'title'         => t('Exporting submissions'),
      'init_message'  => t('Creating export file'),
      'error_message' => t('The export file could not be created because an error occurred.'),
      'operations'    => [
        [
          [
            '\Drupal\tide_webform\Controller\TideWebformResultsExportController',
            'batchProcess',
          ],
          $parameters,
        ],
      ],
      'finished'      => [
        '\Drupal\tide_webform\Controller\TideWebformResultsExportController',
        'batchFinish',
      ],
    ];

    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public static function batchProcess(WebformInterface $webform, EntityInterface $source_entity = NULL, array $export_options = [], &$context = []) {
    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['offset'] = 0;
      $context['sandbox']['max'] = $submission_exporter->getTotal();
      // Store entity ids and not the actual webform or source entity in the
      // $context to prevent "The container was serialized" errors.
      // @see https://www.drupal.org/node/2822023
      $context['results']['webform_id'] = $webform->id();
      $context['results']['source_entity_type'] = ($source_entity) ? $source_entity->getEntityTypeId() : NULL;
      $context['results']['source_entity_id'] = ($source_entity) ? $source_entity->id() : NULL;
      $context['results']['export_options'] = $export_options;
      $submission_exporter->writeHeader();
    }

    // Write CSV records.
    $query = $submission_exporter->getQuery();
    $query->range($context['sandbox']['offset'], $submission_exporter->getBatchLimit());
    $entity_ids = $query->execute();
    $webform_submissions = WebformSubmission::loadMultiple($entity_ids);
    $submission_exporter->writeRecords($webform_submissions);

    // Track progress.
    $context['sandbox']['progress'] += count($webform_submissions);
    $context['sandbox']['offset'] += $submission_exporter->getBatchLimit();

    $context['message'] = t('Exported @count of @total submissions…',
      [
        '@count' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['max'],
      ]);

    // Track finished, if there are results and progress does not match the
    // expected total, calculate finished percentage. A safety guard is added
    // if the current run didn't find any results, then consider it finished as
    // well. This could happen when records are added ore removed during the
    // export.
    if ($entity_ids && $context['sandbox']['max'] > 0 && $context['sandbox']['progress'] !== $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Batch API callback; Completed export.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to download the exported results.
   */
  public static function batchFinish($success, array $results, array $operations) {
    $webform_id = $results['webform_id'];
    $entity_type = $results['source_entity_type'];
    $entity_id = $results['source_entity_id'];

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load($webform_id);
    /** @var \Drupal\Core\Entity\EntityInterface|null $source_entity */
    $source_entity = ($entity_type && $entity_id) ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id) : NULL;
    /** @var array $export_options */
    $export_options = $results['export_options'];

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    if (!$success) {
      $file_path = $submission_exporter->getExportFilePath();
      @unlink($file_path);
      $archive_path = $submission_exporter->getArchiveFilePath();
      @unlink($archive_path);
      \Drupal::messenger()->addStatus(t('Finished with an error.'));
    }
    else {
      $submission_exporter->writeFooter();

      $filename = $submission_exporter->getExportFileName();

      if ($submission_exporter->isArchive()) {
        $submission_exporter->writeExportToArchive();
        $filename = $submission_exporter->getArchiveFileName();
      }

      // Updates extended webform_submision 'processed' field.
      $query = $submission_exporter->getQuery();
      $entity_ids = $query->execute();
      if (isset($export_options['process_submissions'])) {
        $database = \Drupal::database();
        $database->update('webform_submission')
          ->fields(['processed' => 1])
          ->condition('sid', $entity_ids, 'IN')
          ->execute();
        \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache($entity_ids);
      }

      /** @var \Drupal\webform\WebformRequestInterface $request_handler */
      $request_handler = \Drupal::service('webform.request');
      $redirect_url = $request_handler->getUrl($webform,
        $source_entity,
        'webform.results_export',
        ['query' => ['filename' => $filename], 'absolute' => TRUE]);
      return new RedirectResponse($redirect_url->toString());
    }
  }

}
