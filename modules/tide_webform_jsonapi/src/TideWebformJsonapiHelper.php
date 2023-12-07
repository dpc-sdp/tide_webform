<?php

namespace Drupal\tide_webform_jsonapi;

use Respect\Validation\Validator as v;

/**
 * Provides helper functions for tide_webform_jsonapi.
 */
class TideWebformJsonapiHelper {

  /**
   * Extracts validate settings from the webform settings.
   */
  public function webformValidateSettingsExtractor(array $supported_validation, array $original_elements): array {
    $res = [];
    // Iterate through webform values.
    foreach ($original_elements as $w_key => $items) {
      // Iterate through supported validation keys and their values.
      foreach ($supported_validation as $key => $value) {
        // If the current value is an array, check for a match with the key.
        if (is_array($value)) {
          if (array_key_exists($key, $items)) {
            $res[$w_key][$key] = $items[$key];
          }
        }
        else {
          // If the value exists in the items and is not an array,
          // store it in the result.
          if (array_key_exists($value, $items)) {
            $res[$w_key][$value] = $items[$value];
          }
        }
      }
    }
    return $res;
  }

  /**
   * Supported validators.
   */
  public function getSupportedValidateElements() {
    return [
      '#required',
      '#required_error',
      '#pattern',
      '#pattern_error',
    ];
  }

  /**
   * Attached supported validator to Payload.
   */
  public function attachValidateSettingsToPayload(array $payload) {
    $output  = [];
    $mapping = [
      '#required'                => 'required',
      '#required_error'          => 'required',
      '#pattern'                 => 'pattern',
      '#pattern_error'           => 'pattern',
    ];
    foreach ($payload as $key => $value) {
      if (isset($mapping[$key])) {
        $output[$mapping[$key]][] = $value;
      }
    }
    return $output;
  }

  /**
   * Verifies the payload.
   */
  public function validatePayload(array $payload, array $massaged_validates_array, array $original_elements) {
    $results = [];
    // 1st step, check if all required field are submitted.
    foreach ($massaged_validates_array as $key => $item) {
      if (isset($item['required'])) {
        if (!isset($payload[$key])) {
          $payload[$key] = "";
        }
      }
    }
    // 2nd step, check if it passes the validation.
    foreach ($payload as $id => $value) {
      if (array_key_exists($id, $massaged_validates_array)) {
        if (!empty($this->generateErrorString($value, $massaged_validates_array[$id], $original_elements[$id]['#title']))) {
          $results[$id] = $this->generateErrorString($value, $massaged_validates_array[$id], $original_elements[$id]['#title']);
        }
      }
    }
    // 3rd step, check if the field contains correct email or phone numbers.
    foreach ($original_elements as $field_id => $detail) {
      if (!isset($results[$field_id]) && isset($payload[$field_id])) {
        if ($detail['#type'] === 'email') {
          if (v::email()->validate($payload[$field_id]) === FALSE) {
            $results[$field_id] = ['Please provide a valid email address.'];
          }
        }
        if ($detail['#type'] === 'tel') {
          if (v::phone('AU')->validate($payload[$field_id]) === FALSE) {
            $results[$field_id] = ['Please provide a valid Phone number.'];
          }
        }
      }
    }
    return $results;
  }

  /**
   * Generates error messages.
   */
  public function generateErrorString($value, array $arr, $title) {
    $res = [];
    foreach ($arr as $k => $v) {
      if (call_user_func('tide_webform_jsonapi_' . $k . '_validate', $value, $v, $title) !== TRUE) {
        $res[] = call_user_func('tide_webform_jsonapi_' . $k . '_validate', $value, $v, $title);
      }
    }
    return $res;
  }

}
