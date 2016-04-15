@api
Feature: Installation
  As a Drupal developer
  I want Drupal to be installed
  So that I can rely on the build for my project.

  Scenario: Verify that user 1 can log into the site.
    Given I am not logged in
    When I visit "user/login"
    And I fill in "name" with "admin"
    And I fill in "pass" with "admin"
    And I press "Log in"
    Then I should see the link "Log out"
