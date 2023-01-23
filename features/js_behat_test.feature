@api @javascript
Feature: Installation
  As a Drupal developer
  I want to use javaScript in Behat tests
  So that I can rely on the build for my project.

  Scenario: Verify that js behat test works.
    Given I am logged in as a user with the "administrator" role
    When I am on "/admin/modules/update"
    And I should see the link "Check manually"
    And I click "Check manually"
    Then I should see the text "Status message"
