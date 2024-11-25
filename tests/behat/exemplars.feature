@mod @mod_assign @assignsubmission @assignsubmission_comparativejudgement @_file_upload
Feature: In an assignment, teacher can submit blind feedback during grading
  In order to provide a feedback file
  As a teacher
  I need to submit a feedback file.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username    | firstname  | lastname | email                   |
      | student1    | Student    | 1        | student1@example.com    |
      | student2    | Student    | 2        | student2@example.com    |
      | student3    | Student    | 3        | student3@example.com    |
      | nonediting1 | Nonediting | 1        | nonediting@example.com  |
      | nonediting2 | Nonediting | 2        | nonediting2@example.com |
      | nonediting3 | Nonediting | 3        | nonediting3@example.com |
      | teacher1    | teacher    | 1        | teacher@example.com     |
    And the following "course enrolments" exist:
      | user        | course | role           |
      | student1    | C1     | student        |
      | nonediting1 | C1     | teacher        |
      | teacher1    | C1     | editingteacher |
    And the following "activity" exists:
      | activity                                      | assign               |
      | course                                        | C1                   |
      | name                                          | Test assignment name |
      | assignsubmission_comparativejudgement_enabled | 1                    |
      | assignsubmission_onlinetext_enabled           | 1                    |
    And the following config values are set as admin:
      | config           | value | plugin                                |
      | dofakecomparison | 1     | assignsubmission_comparativejudgement |

    And I am on the "Test assignment name" Activity page logged in as teacher1
    When I press "Manage exemplars"
    And I press "Add exemplar"
    And I set the following fields to these values:
      | Title | The first exemplar |
      | Online text | An examplar submissions |
    And I press "Save changes and add another"
    And I set the following fields to these values:
      | Title | The second exemplar |
      | Online text | A second examplar submissions |
    And I press "Save changes"

    And I am on the "Test assignment name" Activity page logged in as student1
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | The submitted text for student1 |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"

  @javascript @oslwip
  Scenario: A teacher can provide a feedback file when grading an assignment.
    And I am on the "Test assignment name" Activity page logged in as teacher1
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Minimum comparisons per judge      | 1                                       |
      | Minimum comparisons per judge      | 1                                       |
      | Minimum comparisons per submission | 1                                       |
      | Judges                             | Non-editing teacher                     |
    And I press "Save and display"

    And I am on the "Test assignment name" Activity page logged in as nonediting1
    And I press "Do comparison"
    And I should see "An examplar submissions"
    And I should see "A second examplar submissions"
    And I press "Choose Left"
    And I should see "The submitted text for student1"
    And I should see "A second examplar submissions"
    And I press "Choose Left"
    And I press "Finish judging"

    And I am on the "Test assignment name" Activity page logged in as teacher1
    And I press "Manage submissions"
    And I should see "The first exemplar" in the "#managesubmissions_table_r0_c0" "css_element"
    And I should see "1" in the "#managesubmissions_table_r0_c8" "css_element"
    And I should see "0" in the "#managesubmissions_table_r0_c9" "css_element"
    And I should see "The second exemplar" in the "#managesubmissions_table_r1_c0" "css_element"
    And I should see "0" in the "#managesubmissions_table_r1_c8" "css_element"
    And I should see "2" in the "#managesubmissions_table_r1_c9" "css_element"
    And I should see "Student 1" in the "#managesubmissions_table_r2_c0" "css_element"
    And I should see "1" in the "#managesubmissions_table_r2_c8" "css_element"
    And I should see "0" in the "#managesubmissions_table_r2_c9" "css_element"

    And I am on the "Test assignment name" Activity page
    And I press "Manage comparisons"
    And I should see "The first exemplar" in the "#managecomparisons_table_r0_c1" "css_element"
    And I should see "The second exemplar" in the "#managecomparisons_table_r0_c3" "css_element"
