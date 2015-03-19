@api
Feature: Installation Verification
  As a Palantir developer,
  I want to know that the skeleton has installed,
  So that I can rely on the build for my project.

  Scenario: Verify that the site and its variables are installed.
    Given I am on homepage
    Then I should see the text "Welcome to Skeleton"
    And I should see the text "Palantir.net's starter website kit."

  Scenario: Verify that user 1 can log into the site.
    Given I am not logged in
    When I visit "user/login"
    And I fill in "name" with "admin"
    And I fill in "pass" with "admin"
    And I press "Log in"
    Then I should see the link "Log out"
    And I should see the link "Add content"
