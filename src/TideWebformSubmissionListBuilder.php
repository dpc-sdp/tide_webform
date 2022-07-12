<?php

namespace Drupal\tide_webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\webform\WebformSubmissionListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class TideWebformSubmissionListBuilder extends WebformSubmissionListBuilder {

  /**
   * Submission state starred.
   */
  const STATE_PROCESSED = 'processed';

  /**
   * Submission state unstarred.
   */
  const STATE_UNPROCESSED = 'unprocessed';

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\webform\TideWebformSubmissionListBuilder $instance */
    $instance = parent::createInstance($container, $entity_type);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuery($keys = '', $state = '', $source_entity = '') {
    $query = parent::getQuery($keys, $state, $source_entity);
    switch ($state) {
      case static::STATE_PROCESSED:
        $query->condition('processed', 1);
        break;

      case static::STATE_UNPROCESSED:
        $query->condition('processed', 0);
        break;

    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $columns = $this->columns;
    $columns['processed'] = [
      'title' => $this->t('processed')
    ];
    $columns['processed']['name'] = 'processed';
    $columns['processed']['format'] = 'value';
    $this->columns = $columns;
    $this->header = parent::buildHeader();
    return $this->header;
  }

}
