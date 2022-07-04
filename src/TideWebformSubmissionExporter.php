<?php

namespace Drupal\tide_webform;

use Drupal\webform\WebformSubmissionExporter;
use Drupal\webform\WebformSubmissionExporterInterface;
use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformExporterManagerInterface;

/**
 * Webform submission exporter.
 */
class TideWebformSubmissionExporter extends WebformSubmissionExporter {

  /**
   * The inner service.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $webformSubmissionExporter;

  /**
   * TideWebformSubmissionExporter function.
   *
   * @param \Drupal\webform\Plugin\WebformSubmissionExporterInterface $webformSubmissionExporter
   *   The webform submission exporter interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Archiver\ArchiverManager $archiver_manager
   *   The archiver manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\Plugin\WebformExporterManagerInterface $exporter_manager
   *   The results exporter manager.
   */
  public function __construct(WebformSubmissionExporterInterface $webformSubmissionExporter, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, StreamWrapperManagerInterface $stream_wrapper_manager, ArchiverManager $archiver_manager, WebformElementManagerInterface $element_manager, WebformExporterManagerInterface $exporter_manager) {
    $this->webformSubmissionExporter = $webformSubmissionExporter;
    parent::__construct($config_factory, $file_system, $entity_type_manager, $stream_wrapper_manager, $archiver_manager, $element_manager, $exporter_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    $query = parent::getQuery();
    $export_options = $this->getExportOptions();

    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();

    if ($export_options['processed']) {
      $query->condition('processed', 0);
    }
    return $query;
  }

}
