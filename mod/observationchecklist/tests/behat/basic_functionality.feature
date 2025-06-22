
@mod @mod_observationchecklist
Feature: Basic observation checklist functionality
  In order to track student progress
  As a teacher
  I need to be able to create and manage observation checklists

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Teacher can create an observation checklist and add items
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Observation Checklist" to section "1" and I fill the form with:
      | Name        | Test Checklist |
      | Description | A test checklist for tracking student progress |
    Then I should see "Test Checklist"
    And I follow "Test Checklist"
    And I should see "Add new item"

  @javascript
  Scenario: Teacher can assess student progress
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I add a "Observation Checklist" to section "1" and I fill the form with:
      | Name        | Progress Tracker |
      | Description | Tracking student skills |
    And I follow "Progress Tracker"
    And I add a checklist item "Can demonstrate basic skills" to the observation checklist
    When I click on "Assessment Interface" "link"
    Then I should see "Student 1"
    And I should see "Can demonstrate basic skills"
