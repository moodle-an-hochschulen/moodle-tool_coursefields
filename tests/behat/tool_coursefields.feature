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
      | Field 6 | Category for test | number   | f6        | d6          |                       |

  @javascript
  Scenario: Manager does set currently empty fields in the given category and subcategory, leaving other categories untouched
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f1" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f2_editor" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f3" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f4" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f5" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f6" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f1" "css_element"
    And I set the following fields to these values:
      | Field 1                          | testcontent1 |
      | Field 2                          | testcontent2 |
      | Field 3                          | 1            |
      | id_customfield_f4_enabled        | 1            |
      | id_customfield_f4_day            | 1            |
      | id_customfield_f4_month          | January      |
      | id_customfield_f4_year           | 2019         |
      | Field 5                          | b            |
      | Field 6                          | 42           |
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
      | Field 6                   | 42           |
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
      | Field 6                   | 42           |
    And I am on "Course 3" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   |   |
      | Field 2                   |   |
      | Field 3                   | 0 |
      | id_customfield_f4_enabled | 0 |
      | Field 5                   |   |
      | Field 6                   |   |
    And I log out

  @javascript
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
      | Field 6                   | 10           |
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
      | Field 6                   | 10           |
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
      | Field 6                   | 10           |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f1" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f2_editor" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f3" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f4" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f5" "css_element"
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f6" "css_element"
    And I set the following fields to these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | Field 3                   | 1            |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
      | Field 6                   | 42           |
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
      | Field 6                   | 42           |
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
      | Field 6                   | 42           |
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
      | Field 6                   | 10           |
    And I log out

  @javascript
  Scenario: Manager does set only empty fields (except checkbox fields) in the given category and subcategory, leaving other categories untouched
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
      | Field 6                   | 10           |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    And I click on "Only set the field for courses where the field is empty" "radio" in the "#fgroup_id_customfieldgroup_f1" "css_element"
    And I click on "Only set the field for courses where the field is empty" "radio" in the "#fgroup_id_customfieldgroup_f2_editor" "css_element"
    And I click on "Only set the field for courses where the field is empty" "radio" in the "#fgroup_id_customfieldgroup_f4" "css_element"
    And I click on "Only set the field for courses where the field is empty" "radio" in the "#fgroup_id_customfieldgroup_f5" "css_element"
    And I click on "Only set the field for courses where the field is empty" "radio" in the "#fgroup_id_customfieldgroup_f6" "css_element"
    And I set the following fields to these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
      | Field 6                   | 42           |
    And I press "Confirm"
    And I should see "An adhoc task has been queued"
    And I run all adhoc tasks
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | Field 1                   | testcontent0 |
      | Field 2                   | testcontent0 |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 2            |
      | id_customfield_f4_month   | February     |
      | id_customfield_f4_year    | 2017         |
      | Field 5                   | a            |
      | Field 6                   | 10           |
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   | testcontent1 |
      | Field 2                   | testcontent2 |
      | id_customfield_f4_enabled | 1            |
      | id_customfield_f4_day     | 1            |
      | id_customfield_f4_month   | January      |
      | id_customfield_f4_year    | 2019         |
      | Field 5                   | b            |
      | Field 6                   | 42           |
    And I am on "Course 3" course homepage
    And I navigate to "Settings" in current page administration
    And the following fields match these values:
      | Field 1                   |   |
      | Field 2                   |   |
      | id_customfield_f4_enabled | 0 |
      | Field 5                   |   |
      | Field 6                   |   |
    And I log out

  @javascript
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
      | Field 6                   | 10           |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f1" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f2_editor" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f3" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f4" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f5" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f6" "css_element"
    And I set the following fields to these values:
      | Field 1 | testcontent1 |
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
      | Field 6                   | 10           |
    And I log out

  @javascript
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
      | Field 6                   | 10           |
    And I press "Save and display"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f1" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f2_editor" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f3" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f4" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f5" "css_element"
    And I click on "Do not change the field at all" "radio" in the "#fgroup_id_customfieldgroup_f6" "css_element"
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
      | Field 6                   | 10           |
    And I log out

  @javascript
  Scenario: Manager cannot bulk-set unique fields (radio buttons are disabled)
    Given the following "custom fields" exist:
      | name    | category          | type     | shortname | description | configdata           |
      | Field 7 | Category for test | text     | f7        | d7          | {"uniquevalues":"1"} |
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then the "id_customfieldupdate_f7_none" "radio" should be enabled
    And the "id_customfieldupdate_f7_all" "radio" should be disabled
    And the "id_customfieldupdate_f7_empty" "radio" should be disabled
    # Basically, we should check if we see this string within the label:has(#id_customfieldupdate_f7_all) element.
    # But Selenium seems not to support :has() selector.
    And I should see "Not possible for unique fields"
    And I log out

  @javascript
  Scenario: Checkbox fields do not support "Only if empty" mode (radio button is disabled)
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then the "id_customfieldupdate_f3_none" "radio" should be enabled
    And the "id_customfieldupdate_f3_all" "radio" should be enabled
    And the "id_customfieldupdate_f3_empty" "radio" should be disabled
    # Basically, we should check if we see this string within the label:has(#id_customfieldupdate_f3_empty) element.
    # But Selenium seems not to support :has() selector.
    And I should see "Not possible for this field type"
    And I log out

  @javascript
  Scenario: Manager cannot set a required field to an empty value
    Given the following "custom fields" exist:
      | name    | category          | type     | shortname | description | configdata       |
      | Field 7 | Category for test | text     | f7        | d7          | {"required":"1"} |
    When I log in as "admin"
    And I am on course index
    And I follow "Category A"
    And I navigate to "Set course fields" in current page administration
    Then the "id_customfieldupdate_f7_none" "radio" should be enabled
    And the "id_customfieldupdate_f7_all" "radio" should be enabled
    And the "id_customfieldupdate_f7_empty" "radio" should be enabled
    And I click on "Overwrite the field for all courses" "radio" in the "#fgroup_id_customfieldgroup_f7" "css_element"
    And I set the following fields to these values:
      | Field 7 | |
    And I press "Confirm"
    Then I should see "is required and cannot be empty" in the "#id_error_customfield_f7" "css_element"
    And I should not see "An adhoc task has been queued"
    And I log out
