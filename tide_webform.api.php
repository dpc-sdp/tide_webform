<?php

/**
 * @file
 * API.
 */

/**
 * Alter data before posting.
 *
 * By implementing this hook and enabling `tide_remote_post` as your POST
 * handler, you can modify the body of POST.
 * This hook mostly uses for dealing with composite data.
 *
 * @param array $data
 *   Array keyed by webform field name.
 */
function hook_tide_webform_post_alter(array &$data, \Drupal\webform\Plugin\WebformHandlerInterface $handler) {
  if ($handler->getWebform()->id() == 'an_example_tide_form_') {
    if ($data['a_field_name']) {
      $data['a_field_name'] = ['hello word'];
    }
  }

}
