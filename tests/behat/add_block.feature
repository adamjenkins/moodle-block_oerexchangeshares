@block @block_oerexchangeshares @javascript
Feature: Add the OER Exchange your shares block to the Dashboard
  In order to track the status of what I have shared
  As a user
  I need to be able to add the block to my Dashboard

  Scenario: An admin adds the block to their Dashboard and it renders correctly
    Given I log in as "admin"
    And I visit "/my/"
    And I turn editing mode on
    When I add the "OER Exchange: your shares" block
    Then I should see "You haven't shared anything to the OER Exchange yet." in the "OER Exchange: your shares" "block"
    And "Share a new resource" "link" should exist in the "OER Exchange: your shares" "block"
