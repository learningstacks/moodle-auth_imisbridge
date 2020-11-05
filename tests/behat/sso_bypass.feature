@auth @auth_imisbridge
Feature: Ability to bypass SSO.
  In order to support the LMS
  As a privileged user
  I need to be able to bypass SSO and login manually.

  @javascript
  Scenario: Visit login page with SSO bypass
    When I visit "/login/index/php?nosso"
    And I wait to be redirected
    Then I should see "SSO Login Page"

  @javascript
  Scenario: Visit course page with SSo bypass
    When I visit "/course/view.php?id=1&nosso"
    And I wait to be redirected
    Then I should see "SSO Login Page"