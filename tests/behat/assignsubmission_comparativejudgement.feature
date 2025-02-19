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
      | student2    | C1     | student        |
      | student3    | C1     | student        |
      | nonediting1 | C1     | teacher        |
      | nonediting2 | C1     | teacher        |
      | nonediting3 | C1     | teacher        |
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

    And I am on the "Test assignment name" Activity page logged in as student1
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | The submitted text for student1 |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"

    And I am on the "Test assignment name" Activity page logged in as student2
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | The submitted text for student2 |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"

    And I am on the "Test assignment name" Activity page logged in as student3
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | The submitted text for student3 |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"

  @javascript
  Scenario: A teacher can provide a feedback file when grading an assignment.
    And I am on the "Test assignment name" Activity page logged in as teacher1
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Minimum comparisons per judge      | 2                                       |
      | Minimum comparisons per judge      | 3                                       |
      | Minimum comparisons per submission | 2                                       |
      | Introduction for judges            | The introduction to the judging process |
      | Judges                             | Non-editing teacher                     |
    And I press "Save and display"
    And I press "Manage judges"
    And I should see "Nonediting 1" in the "#managejudges_table_r0" "css_element"
    And I should see "Nonediting 2" in the "#managejudges_table_r1" "css_element"
    And I am on the "Test assignment name" Activity page
    And I press "Manage submissions"
    And I should see "Student 1" in the "#managesubmissions_table_r0" "css_element"
    And I should see "Student 2" in the "#managesubmissions_table_r1" "css_element"
    And I should see "Student 3" in the "#managesubmissions_table_r2" "css_element"

    And I am on the "Test assignment name" Activity page logged in as nonediting1
    And I press "Do comparison"
    And I should see "The introduction to the judging process"
    And I follow "Continue"
    And I should see "The submitted text for student1"
    And I should see "The submitted text for student2"
    And I press "Choose Left"
    And I should see "The submitted text for student1"
    And I should see "The submitted text for student3"
    And I press "Choose Right"
    And I should see "The submitted text for student2"
    And I should see "The submitted text for student3"
    And I press "Choose Right"
    And I press "Finish judging"

    And I am on the "Test assignment name" Activity page logged in as nonediting2
    And I press "Do comparison"
    And I should see "The introduction to the judging process"
    And I follow "Continue"
    And I should see "The submitted text for student1"
    And I should see "The submitted text for student2"
    And I press "Choose Left"
    And I should see "The submitted text for student1"
    And I should see "The submitted text for student3"
    And I press "Choose Left"
    And I should see "The submitted text for student2"
    And I should see "The submitted text for student3"
    And I press "Choose Left"
    And I press "Finish judging"

    And I am on the "Test assignment name" Activity page logged in as nonediting3
    And I press "Do comparison"
    And I should see "The introduction to the judging process"
    And I follow "Continue"
    And I should see "The submitted text for student1"
    And I should see "The submitted text for student2"
    And I press "Choose Left"

    And I am on the "Test assignment name" Activity page logged in as teacher1
    And I press "Manage judges"
    And I should see "1 : 2" in the "Nonediting 1" "table_row"
    And I should see "3" in the "Nonediting 1" "table_row"
    And I should see "3 : 0" in the "Nonediting 2" "table_row"
    And I should see "3" in the "Nonediting 2" "table_row"
    And I should see "1 : 0" in the "Nonediting 3" "table_row"
    And I should see "1" in the "Nonediting 3" "table_row"

    And I am on the "Test assignment name" Activity page
    And I press "Manage submissions"
    And I should see "5" in the "#managesubmissions_table_r0_c3" "css_element"
    And I should see "1" in the "#managesubmissions_table_r0_c8" "css_element"
    And I should see "4" in the "#managesubmissions_table_r0_c9" "css_element"
    And I should see "5" in the "#managesubmissions_table_r1_c3" "css_element"
    And I should see "4" in the "#managesubmissions_table_r1_c8" "css_element"
    And I should see "1" in the "#managesubmissions_table_r1_c9" "css_element"
    And I should see "4" in the "#managesubmissions_table_r2_c3" "css_element"
    And I should see "2" in the "#managesubmissions_table_r2_c8" "css_element"
    And I should see "2" in the "#managesubmissions_table_r2_c9" "css_element"

    And I press "Calculate scores"
    And I should see "Comparison done"
    And I should see "Last reliability: 0"

    And I press "Copy grades to gradebook"
    And I should see "Grades copied to gradebook"

    And I am on the "Course 1" Course page
    And I navigate to "Grades" in current page administration
    And I should see "1.00" in the "Student 1" "table_row"
    And I should see "4.00" in the "Student 2" "table_row"
    And I should see "2.00" in the "Student 3" "table_row"

    And I am on the "Test assignment name" Activity page
    And I press "Manage judges"
    And I click on "#managejudges_table_r0_c10 .excludeentity" "css_element"
    And I click on "#managejudges_table_r2_c10 .excludeentity" "css_element"

    And I am on the "Test assignment name" Activity page
    And I press "Manage submissions"
    And I press "Calculate scores"
    And I press "Copy grades to gradebook"

    And I am on the "Course 1" Course page
    And I navigate to "Grades" in current page administration
    And I should see "0.00" in the "Student 1" "table_row"
    And I should see "1.00" in the "Student 2" "table_row"
    And I should see "2.00" in the "Student 3" "table_row"

    And I am on the "Test assignment name" Activity page
    And I press "Manage comparisons"
    And I should see "Manage comparisons (7)"
    And I should see "Student 2" in the "#managecomparisons_table_r0_c1" "css_element"
    And I should see "Student 1" in the "#managecomparisons_table_r0_c3" "css_element"
    And I should see "Left" in the "#managecomparisons_table_r0_c6" "css_element"
    And I click on "#managecomparisons_table_r0_c7 .icon" "css_element"
    And I press "Delete"
    And I should see "Manage comparisons (6)"
    Then I should see "Student 1" in the "#managecomparisons_table_r0_c1" "css_element"
