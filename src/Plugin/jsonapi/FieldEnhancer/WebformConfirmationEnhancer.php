<?php

namespace Drupal\tide_webform\Plugin\jsonapi\FieldEnhancer;

use Drupal\Component\Uuid\Uuid;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exposes confirmation message from notes field.
 *
 * This plugin only works on `notes` field, and the confirmation message field
 * should be `hidden` type with `confirmation_message` machine name.
 *
 * @ResourceFieldEnhancer(
 *   id = "webform_confirmation_enhancer",
 *   label = @Translation("Webform confirmation enhancer"),
 *   description = @Translation("Webform confirmation enhancer.")
 * )
 */
class WebformConfirmationEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (is_string($data) && Uuid::isValid($data)) {
      $webform_submission_by_uuid = \Drupal::entityTypeManager()
        ->getStorage('webform_submission')
        ->loadByProperties(['uuid' => $data]);
      if (!empty($webform_submission_by_uuid)) {
        $webform_submission_by_uuid = reset($webform_submission_by_uuid);
        $message = $webform_submission_by_uuid->getElementData('confirmation_message');
        // We only check if the [webform:handler token exists or not, as it may
        // involve communicating outside if it is still in here, meaning the
        // communication gets failed.
        $is_unprocessed_token = (strpos(print_r($message, TRUE), '[webform:handler:') !== FALSE) ? TRUE : FALSE;
        if ($is_unprocessed_token) {
          // Removes the failed webform_submission.
          $webform_submission_by_uuid->delete();
          throw new NotFoundHttpException();
        }
        return $message;
      }
    }
    // We don't want to expose notes to outside.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'anyOf' => [
        ['type' => 'array'],
        ['type' => 'boolean'],
        ['type' => 'null'],
        ['type' => 'number'],
        ['type' => 'object'],
        ['type' => 'string'],
      ],
    ];
  }

}
