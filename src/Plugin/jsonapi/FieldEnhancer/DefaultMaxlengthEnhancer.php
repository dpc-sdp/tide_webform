<?php

namespace Drupal\tide_webform\Plugin\jsonapi\FieldEnhancer;

use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Alters date to add default value to the text based fields.
 *
 * @ResourceFieldEnhancer(
 *   id = "default_maxlength_enhancer",
 *   label = @Translation("Default Maxlength enhancer"),
 *   description = @Translation("Default Maxlength enhancer")
 * )
 */
class DefaultMaxlengthEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if ($cache = \Drupal::cache()->get('webform_text_fields_default_maxlength')) {
      $result = $cache->data;
    }
    else {
      $types = [];
      $result = Yaml::decode($data);
      /** @var Drupal\webform\Plugin\WebformElementManager $plugin_webform */
      $plugin_webform = \Drupal::service('plugin.manager.webform.element');
      foreach ($plugin_webform->getInstances() as $id => $instance) {
        if ($instance instanceof TextBase) {
          $types[] = $id;
        }
      }
      $this->updateElementsWithDefaultValue($types, $result);
      \Drupal::cache()->set('webform_text_fields_default_maxlength', Yaml::encode($result));
    }
    return $result;
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

  /**
   * Update elements with the default value.
   */
  public function updateElementsWithDefaultValue($types, &$array) {
    foreach ($array as &$value) {
      if (is_array($value)) {
        if (isset($value['#type']) && in_array($value['#type'], $types) && !isset($value['#default_value'])) {
          $value['#default_value'] = 255;
        }
        self::updateElementsWithDefaultValue($types, $value);
      }
    }
  }

}
