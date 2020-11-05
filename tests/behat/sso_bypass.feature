@auth @auth_imisbridge
Feature: Ability to bypass SSO.
  In order to support the LMS
  As a privileged user
  I need to be able to bypass SSO and login manually.

  Background:
    Given the following "users" exist:
      | username       | suspended | deleted |
      | active_user    | 0         | 0       |

  @javascript
  Scenario: Visit login page with SSO bypass
    When I visit "/login/index.php?nosso"
    And I set the field "Username" to "active_user"
    And I set the field "Password" to "active_user"
    And I press "Log in"
    Then I should see "You are logged in as" in the "page-footer" "region"
    And I should see "Acceptance test site"
