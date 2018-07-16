@tide
Feature: Content Rating block.

  Ensure that the Content Rating block visibility is correct

  @api @suggest
  Scenario: Content Rating block
    Given page content:
      | title                          | moderation_state | field_show_content_rating |
      | [TEST] Page content rating ON  | published        | 1                         |
      | [TEST] Page content rating OFF | published        | 0                         |

    Given I am an anonymous user

    When I visit page "[TEST] Page content rating ON"
    And I should see an "#block-tide-webform-content-rating" element

    When I visit page "[TEST] Page content rating OFF"
    And I should not see an "#block-tide-webform-content-rating" element
