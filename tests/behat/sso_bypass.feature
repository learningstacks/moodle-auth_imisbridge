@auth @auth_imisbridge
Feature: Ability to bypass SSO.
  In order to support the LMS
  As a privileged user
  I need to be able to bypass SSO and login manually.

  Background:
    Given the following config values are set as admin:
      | forcelogin     | 1                                                        |                  |
      | auth           | imisbridge                                               |                  |
      | imis_home_url  | /auth/imisbridge/tests/behat/fixtures/imis_home.php      | auth_imisbridge  |
      | sso_login_url  | /auth/imisbridge/tests/behat/fixtures/sso_login.php      | auth_imisbridge  |
      | sso_logout_url | /auth/imisbridge/tests/behat/fixtures/sso_logout.php     | auth_imisbridge  |
      | create_user    | 0                                                        | auth_imisbridge  |
      | base_api_url   | /auth/imisbridge/tests/behat/fixtures/imisbridge_api.php | local_imisbridge |
    And the following "users" exist:
      | username       |
      | active_user    |
    And the following "courses" exist:
      | shortname | idnumber | fullname              |
      | course1   | course1  | This is test course 1 |

  @javascript
  Scenario: Visit login page with SSO bypass
    When I visit "/login/index.php?nosso"
    Then I should be on "/login/index.php"
    When I set the field "Username" to "active_user"
    And I set the field "Password" to "active_user"
    When I press "Log in"
    Then I should see "You are logged in as" in the "page-footer" "region"
    And I should see "Acceptance test site"
