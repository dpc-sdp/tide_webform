<?php

namespace Drupal\tide_webform;

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
  // protected function buildFilterForm() {
  //   $formBuilder = parent::buildFilterForm();
  //   $state_options = $formBuilder['filter']['state']['#options'];
  //   $state_options[static::STATE_PROCESSED] = $this->t('Processed [@total]', ['@total' => $this->getTotal(NULL, static::STATE_PROCESSED, $this->sourceEntityTypeId)]);
  //   $state_options[static::STATE_UNPROCESSED] = $this->t('Unprocessed [@total]', ['@total' => $this->getTotal(NULL, static::STATE_UNPROCESSED, $this->sourceEntityTypeId)]);

  //   $formBuilder['filter']['state']['#options'] = $state_options;
  //   return $formBuilder;
  // }

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

}
