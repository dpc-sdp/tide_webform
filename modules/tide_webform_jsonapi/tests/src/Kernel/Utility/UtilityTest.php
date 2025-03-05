<?php

declare(strict_types = 1);

namespace Drupal\Tests\tide_webform_jsonapi\Kernel\Utility;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the JSON:API AddWebform resource.
 *
 * @group tide_webform_jsonapi
 * @coversDefaultClass \Drupal\tide_webform_jsonapi\Resource\AddWebform
 */
final class UtilityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'tide_webform_jsonapi',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->tideWebformJsonapiHelper = \Drupal::service('tide_webform_jsonapi.helper');
  }

  /**
   * Tests if the functions could convert and validate payloads.
   *
   * @covers ::webformValidateSettingsExtractor
   * @covers ::getSupportedValidateElements
   * @covers ::attachValidateSettingsToPayload
   * @covers ::validatePayload
   * @covers ::generateErrorString
   *
   * @dataProvider webformDataProvider
   */
  public function testWebformValidation($payload, $webform_settings, $expected) {
    $supported_validations = $this->tideWebformJsonapiHelper->getSupportedValidateElements();
    $results = $this->tideWebformJsonapiHelper->webformValidateSettingsExtractor($supported_validations, $webform_settings);
    $new_array = [];
    foreach ($results as $key => $r) {
      $new_array[$key] = $this->tideWebformJsonapiHelper->attachValidateSettingsToPayload($r);
    }
    $errors = $this->tideWebformJsonapiHelper->validatePayload($payload, $new_array);
    $this->assertEquals($expected, $errors);
  }

  /**
   * Data Provider.
   */
  public function webformDataProvider() {
    return [
      [
        [
          "comments"              => "TEST Content Rating comment1",
          "test_email"            => "",
          "url"                   => "/home",
          "was_this_page_helpful" => "Yes",
          "test_extfield"         => "",
        ],
        [
          'url'                   => [
            "#type"  => "hidden",
            "#title" => "URL",
          ],
          'was_this_page_helpful' => [
            "#type"     => "radios",
            "#title"    => "Was this page helpful?",
            "#options"  => "yes_no",
            "#required" => TRUE,
          ],
          'test_email'            => [
            "#type"          => "email",
            "#title"         => "testemail",
            "#required"      => TRUE,
            '#required_error' => 'The field is mandatory 1.',
            "#pattern"       => "/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}$/",
            "#pattern_error" => "The value does not match the criteria 1.",
          ],
          'test_extfield'         => [
            "#type"                    => "textfield",
            "#title"                   => "testextfield",
            "#required"                => TRUE,
            "#required_error"          => "The field is mandatory.",
            "#pattern"                 => "/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}$/",
            "#pattern_error"           => "The value does not match the criteria 2.",
            "#counter_type"            => "character",
            "#counter_minimum"         => 1,
            "#counter_maximum"         => 5,
            "#counter_maximum_message" => "sdfgsdfg",
          ],
        ],
        [
          "test_email"    => [
            "The field is mandatory 1.",
            "The value does not match the criteria 1.",
          ],
          "test_extfield" => [
            "The field is mandatory.",
            "The value does not match the criteria 2.",
          ],
        ],
      ],
      [
        [
          "comments"              => "TEST Content Rating comment1",
          "test_email"            => "",
          "url"                   => "/home",
          "was_this_page_helpful" => "Yes",
          "test_extfield"         => "",
        ],
        [
          'url'                   => [
            "#type"  => "hidden",
            "#title" => "URL",
          ],
          'was_this_page_helpful' => [
            "#type"     => "radios",
            "#title"    => "Was this page helpful?",
            "#options"  => "yes_no",
            "#required" => TRUE,
            "#required_error" => 'The field is mandatory 1.',
          ],
          'test_email'            => [
            "#type"          => "email",
            "#title"         => "testemail",
            "#required"      => TRUE,
            "#pattern"       => "/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}$/",
          ],
          'test_extfield'         => [
            "#type"                    => "textfield",
            "#title"                   => "testextfield",
            "#required"                => TRUE,
            "#required_error"          => "The field is mandatory.",
            "#pattern"                 => "/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}$/",
            "#counter_type"            => "character",
            "#counter_minimum"         => 1,
            "#counter_maximum"         => 5,
            "#counter_maximum_message" => "sdfgsdfg",
          ],
        ],
        [
          "test_email"    => [
            "The field is mandatory.",
            "The value does not match the criteria.",
          ],
          "test_extfield" => [
            "The field is mandatory.",
            "The value does not match the criteria.",
          ],
        ],
      ],
    ];
  }

}
