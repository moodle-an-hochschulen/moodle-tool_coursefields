@tool @tool_coursefields
Feature: The course fields tool allows a manager to set custom course fields in bulk
  In order to avoid unnecessary click orgies
  As a manager
  I need to set custom course fields in bulk

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber | visible |
      | Category A | 0        | CATA     | 0       |
      | Category B | CATA     | CATB     | 0       |
      | Category C | 0        | CATC     | 0       |
    And the following "courses" exist:
      | fullname | shortname | category | visible | startdate  |
      | Course 1 | C1        | CATA     | 1       | 1546300800 |
      | Course 2 | C2        | CATB     | 1       | 1546300800 |
      | Course 3 | C3        | CATC     | 1       | 1546300800 |
    And the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    And the following "custom fields" exist:
      | name    | category          | type     | shortname | description | configdata            |
      | Field 1 | Category for test | text     | f1        | d1          |                       |
      | Field 2 | Category for test | textarea | f2        | d2          |                       |
      | Field 3 | Category for test | checkbox | f3        | d3          |                       |
      | Field 4 | Category for test | date     | f4        | d4          |                       |
      | Field 5 | Category for test | select   | f5        | d5          | {"options":"a\nb\nc"} |

  Scenario: Manager does set currently empty fields in the given category and subcategory, leaving other categories untouched
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    And I set the following fields to these values:
      | id_customfieldcheckbox_f1        | 1            |
      | Field 1                          | testcontent1 |
      | id_customfieldcheckbox_f2_editor | 1            |
      | Field 2                          | testcontent2 |
      | id_customfieldcheckbox_f3        | 1            |
      | Field 3                          | 1            |
      | id_customfieldcheckbox_f4        | 1            |
      | id_customfield_f4_enabled        | 1            |
      | id_customfield_f4_day            | 1            |
      | id_customfield_f4_month          | January      |
      | id_customfield_f4_year           | 2019         |
      | id_customfieldcheckbox_f5        | 1            |
      | Field 5                          | b            |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | Field 3                   | 1            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | Field 3                   | 1            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
    And I am on "Course 3" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   |   |
      | Field 2                   |   |
      | Field 3                   | 0 |
      | id_customfield_f4_enabled | 0 |
      | Field 5                   |   |
    And I log out

  Scenario: Manager does overwrite existing field values in the given category and subcategory, leaving other categories untouched
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I press "Save and display"
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I press "Save and display"
    And I am on "Course 3" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then I set the following fields to these values:
      | id_customfieldcheckbox_f1        | 1            |
      | Field 1                          | testcontent1 |
      | id_customfieldcheckbox_f2_editor | 1            |
      | Field 2                          | testcontent2 |
      | id_customfieldcheckbox_f3        | 1            |
      | Field 3                          | 1            |
      | id_customfieldcheckbox_f4        | 1            |
      | id_customfield_f4_enabled        | 1            |
      | id_customfield_f4_day            | 1            |
      | id_customfield_f4_month          | January      |
      | id_customfield_f4_year           | 2019         |
      | id_customfieldcheckbox_f5        | 1            |
      | Field 5                          | b            |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | Field 3                   | 1            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | Field 3                   | 1            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
    And I am on "Course 3" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I log out

  Scenario: Manager does overwrite only one existing field, leaving the other fields untouched
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then I set the following fields to these values:
      | id_customfieldcheckbox_f1        | 1            |
      | Field 1                          | testcontent1 |
      | id_customfieldcheckbox_f2_editor | 0            |
      | id_customfieldcheckbox_f3        | 0            |
      | id_customfieldcheckbox_f4        | 0            |
      | id_customfieldcheckbox_f5        | 0            |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I log out

  Scenario: Manager does not overwrite any fields, thus leaving all existing values untouched
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then I set the following fields to these values:
      | id_customfieldcheckbox_f1        | 0 |
      | id_customfieldcheckbox_f2_editor | 0 |
      | id_customfieldcheckbox_f3        | 0 |
      | id_customfieldcheckbox_f4        | 0 |
      | id_customfieldcheckbox_f5        | 0 |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | Field 3                   | 0            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
    And I log out

  Scenario: Manager does overwrite a unique field with the same string without problems
    Given the following "custom fields" exist:
      | name    | category          | type     | shortname | description | configdata           |
      | Field 6 | Category for test | text     | f6        | d6          | {"uniquevalues":"1"} |
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then I set the following fields to these values:
      | id_customfieldcheckbox_f6 | 1         |
      | Field 6                   | nonunique |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 6 | nonunique |
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 6 | nonunique |
    And I log out

  Scenario: Manager does overwrite a required field with an empty value without problems
    Given the following "custom fields" exist:
      | name    | category          | type     | shortname | description | configdata       |
      | Field 6 | Category for test | text     | f6        | d6          | {"required":"1"} |
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then I set the following fields to these values:
      | id_customfieldcheckbox_f6 | 1 |
      | Field 6                   |   |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 6 | |
    And I log out
