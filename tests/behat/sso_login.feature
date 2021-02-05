@moodle_imis @auth @auth_imisbridge
Feature: SSO Auth with IMIS.
  In order to integrate with IMIS
  As a user
  I need to login via IMIS SSO.

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
      | username       | suspended | deleted |
      | active_user    | 0         | 0       |
      | suspended_user | 1         | 0       |
      | deleted_user   | 0         | 1       |
    And the following "courses" exist:
      | shortname | idnumber | fullname |
      | course1   | course1  | This is test course 1 |

  Scenario: Visit homepage with no token, successful SSO.
    When I visit "/" with no IMIS token
    And I wait to be redirected
    Then I should see "SSO Login Page"
    When I set the field "username" to "active_user"
    And I press "Log In"
    And I wait to be redirected
    Then I should see "You are logged in as"

  Scenario: Redirect to SSO Login Page when not logged in.
    When I visit "/login/index.php" with no IMIS token
    And I wait to be redirected
    Then I should see "SSO Login Page"
    When I set the field "username" to "active_user"
    And I press "Log In"
    Then I should see "You are logged in as"

  Scenario: Redirect to course after SSO login.
    When I am on "This is test course 1" course homepage
    Then I should see "SSO Login Page"
    When I set the field "username" to "active_user"
    And I press "Log In"
    Then I should see "You are logged in as"
    And I should see "This is test course 1"

  Scenario: Go to course when already logged into IMIS.
    When I visit course "This is test course 1" homepage with token "active_user"
    Then I should see "You are logged in as"
    And I should see "This is test course 1"

  Scenario: Display error then go to SSO Logout page when invalid token.
    When I visit course "This is test course 1" homepage with token "invalid_token"
    Then I should see "IMIS User Not Found"
    When I press "Continue"
    Then I should see "SSO Logout Page"

  Scenario: Display error then go to SSO Logout page when IMIS user not found.
    When I visit course "This is test course 1" homepage with token "no_imis_user"
    Then I should see "IMIS User Not Found"
    When I press "Continue"
    Then I should see "SSO Logout Page"

  Scenario: Display error then goto IMIS home when Moodle user not found.
    When I visit course "This is test course 1" homepage with token "new_user"
    Then I should see "LMS User Not Found"
    When I press "Continue"
    Then I should see "IMIS Home Page"

  Scenario: Go to edit user when Moodle user is not completely set up.
    Given the following config values are set as admin:
      | create_user | 1 | auth_imisbridge |
    When I visit course "This is test course 1" homepage with token "new_user"
    Then I should be on "/user/edit.php"

  Scenario: Display error then goto IMIS home when Moodle user has been deleted.
    When I visit course "This is test course 1" homepage with token "deleted_user"
    Then I should see "LMS User Not Found"
    When I press "Continue"
    Then I should see "IMIS Home Page"

  Scenario: Display error then goto IMIS home when Moodle user has been suspended
    When I visit course "This is test course 1" homepage with token "suspended_user"
    Then I should see "LMS User Has Been Suspended"
    When I press "Continue"
    Then I should see "IMIS Home Page"

#    Exercise prelogin and logon page hooks
#     Missing urls (login, logout, home_