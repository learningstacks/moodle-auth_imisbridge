@auth @auth_imisbridge
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
      | shortname | idnumber | fullname              |
      | course1   | course1  | This is test course 1 |

  @javascript
  Scenario: Visit homepage with no token, successful SSO.
    When I visit "/"
    And I wait to be redirected
    Then I should see "SSO Login Page"
    When I set the field "username" to "active_user"
    And I press "Log In"
    And I wait to be redirected
    Then I should see "Acceptance test site"

  Scenario: Try directly accessing login page.
    When I visit "/login/index.php"
    And I wait to be redirected
    Then I should see "SSO Login Page"
    When I set the field "username" to "active_user"
    And I press "Log In"
    And I wait to be redirected
    Then I should see "Acceptance test site"


  @javascript
  Scenario: Visit course homepage, no token, successful SSO.
    When I am on the "course1" "Course" page
    And I wait to be redirected
    Then I should see "SSO Login Page"
    When I set the field "username" to "active_user"
    And I press "Log In"
    And I wait to be redirected
    Then I should see "This is test course 1"

  @javascript
  Scenario: Visit course homepage, valid token, successful SSO
    When I visit course "course1" homepage with token "active_user"
    And I wait to be redirected
    Then I should see "This is test course 1"

  @javascript
  Scenario: Invalid token
    When I visit course "course1" homepage with token "invalid_token"
    And I wait to be redirected
    Then I should see "IMIS User Not Found"
    When I press "Continue"
    And I wait to be redirected
    Then I should see "SSO Logout Page"

  @javascript
  Scenario: No IMIS user.
    When I visit course "course1" homepage with token "no_imis_user"
    And I wait to be redirected
    Then I should see "IMIS User Not Found"
    When I press "Continue"
    And I wait to be redirected
    Then I should see "SSO Logout Page"

  @javascript
  Scenario: No Moodle User, no create option.
    When I visit course "course1" homepage with token "new_user"
    And I wait to be redirected
    Then I should see "LMS User Not Found"
    When I press "Continue"
    And I wait to be redirected
    Then I should see "IMIS Home Page"

  @javascript
  Scenario: No Moodle user, create option, incomplete user setup.
    Given the following config values are set as admin:
      | create_user | 1 | auth_imisbridge |
    When I visit course "course1" homepage with token "new_user"
    And I wait to be redirected
    Then I should be on "/user/edit.php"

  @javascript
  Scenario: Deleted Moodle user.
    When I visit course "course1" homepage with token "deleted_user"
    And I wait to be redirected
    Then I should see "LMS User Not Found"
    When I press "Continue"
    And I wait to be redirected
    Then I should see "IMIS Home Page"

  @javascript
  Scenario: Suspended Moodle user.
    When I visit course "course1" homepage with token "suspended_user"
    And I wait to be redirected
    Then I should see "LMS User Has Been Suspended"
    When I press "Continue"
    And I wait to be redirected
    Then I should see "IMIS Home Page"

#    Exercise prelogin and logon page hooks
#     Missing urls (login, logout, home_