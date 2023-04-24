@tide
Feature: Webform "reCAPTCHA" exists.

  Ensure that the 'reCAPTCHA' THIRD PARTY SETTINGS exists

  @api @suggest
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "administrator" role
    When I visit "admin/structure/webform/manage/tide_webform_content_rating/settings"
    Then I should see the text "TIDE WEBFORM RECAPTCHA"
    Then I check the box "Enable reCAPTCHA"
    And I press "Save"
    When I send a GET request to "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_webform_content_rating"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "data[0].attributes.third_party_settings.tide_webform.tide_webform_recaptcha" should be equal to "1"
    When I visit "admin/structure/webform/manage/tide_webform_content_rating/settings"
    Then I should see the text "TIDE WEBFORM RECAPTCHA"
    Then I uncheck the box "Enable reCAPTCHA"
    And I press "Save"
    When I send a GET request to "/api/v1/webform/webform?filter[drupal_internal__id][value]=tide_webform_content_rating"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "data[0].attributes.third_party_settings.tide_webform.tide_webform_recaptcha" should be equal to "0"
