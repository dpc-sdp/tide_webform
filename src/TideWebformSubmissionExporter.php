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
   *   Lsdfsdf.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Lsdfsdf.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Lsdfsdf.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Lsdfsdf.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   Lsdfsdf.
   * @param \Drupal\Core\Archiver\ArchiverManager $archiver_manager
   *   Lsdfsdf.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   Lsdfsdf.
   * @param \Drupal\webform\Plugin\WebformExporterManagerInterface $exporter_manager
   *   Lsdfsdf.
   */
  public function __construct(WebformSubmissionExporterInterface $webformSubmissionExporter, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, StreamWrapperManagerInterface $stream_wrapper_manager, ArchiverManager $archiver_manager, WebformElementManagerInterface $element_manager, WebformExporterManagerInterface $exporter_manager) {
    $this->webformSubmissionExporter = $webformSubmissionExporter;
    parent::__construct($config_factory, $file_system, $entity_type_manager, $stream_wrapper_manager, $archiver_manager, $element_manager, $exporter_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    parent::getQuery();
    print "Decorator PROTECT Protected\n";
  }

}
