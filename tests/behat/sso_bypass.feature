@moodle-imis @auth @auth_imisbridge
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
      | username |
      | admin1   |

  Scenario: Visit login page with SSO bypass, not logged in
    When I visit "/login/index.php?nosso" with no IMIS token
    When I set the field "Username" to "admin1"
    And I set the field "Password" to "admin1"
    When I press "Log in"
    Then I should see "You are logged in as"

