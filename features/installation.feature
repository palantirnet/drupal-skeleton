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
    # Not "I should see the link 'Log out'": on a cold render cache the account
    # menu block is a BigPipe placeholder, which this non-JS scenario never
    # resolves. "Member for" is rendered server-side on the profile page.
    Then I should see the text "Member for"
