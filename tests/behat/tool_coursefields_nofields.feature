@tool @tool_coursefields
Feature: The course fields tool does not allow a manager to set custom course fields before creating custom course field categories
  In order to use the tool
  As a manager
  I need to create custom course fields first

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber | visible |
      | Category 1 | 0        | CAT1     | 0       |
      | Category 2 | CAT1     | CAT2     | 0       |
      | Category 3 | 0        | CAT3     | 0       |
    And the following "courses" exist:
      | fullname | shortname | category | visible | startdate  |
      | Course 1 | C1        | CAT2     | 1       | 1546300800 |
      | Course 2 | C2        | CAT2     | 1       | 1546300800 |
      | Course 3 | C3        | CAT3     | 1       | 1546300800 |

  Scenario: Manager is not able to use the course fields tool if there are not any course field categories
    When I log in as "admin"
    And I am on course index
    And I follow "Category 1"
    Then "Set course fields" "link" should not exist in current page administration
