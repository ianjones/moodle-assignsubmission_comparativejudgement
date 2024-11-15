<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    assignsubmission_comparativejudgement
 * @copyright 2020 Andrew Hancox at Open Source Learning <andrewdchancox@googlemail.com>
 * @copyright 2020 Ian Jones at Loughborough University <I.Jones@lboro.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/mod/assign/submission/comparativejudgement/locallib.php');

use assignsubmission_comparativejudgement\comparisonmanager;

/**
 * @group assignsubmission_comparativejudgement
 */
class comparisongetalljudges_test extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_getalljudges_fakerole_assignment_submitted() {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $submittedstudent = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        // Assignment with submissions.
        $secondassign = $this->create_instance($course, [
                'name'                                          => 'Assignment with submissions',
                'duedate'                                       => time(),
                'attemptreopenmethod'                           => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                   => 3,
                'submissiondrafts'                              => 1,
                'assignsubmission_onlinetext_enabled'           => 1,
                'assignsubmission_comparativejudgement_enabled' => 1,
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);

        $this->add_submission($submittedstudent, $secondassign);
        $this->submit_for_grading($submittedstudent, $secondassign);

        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        $editingteacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        $plugin->set_config('judges',
                implode(',', [
                        \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS,
                        $teacherroleid,
                        $editingteacherroleid])
        );

        $comparisonmanager = new comparisonmanager($editingteacher->id, $secondassign);
        $this->assertCount(4, $comparisonmanager->getalljudges());

        $plugin->set_config('judges',
                implode(',', [
                        \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED,
                        $teacherroleid,
                        $editingteacherroleid])
        );

        $this->assertCount(3, $comparisonmanager->getalljudges());

        $plugin->set_config('judges',
                implode(',', [
                        $teacherroleid,
                        $editingteacherroleid])
        );

        $this->assertCount(2, $comparisonmanager->getalljudges());

        $plugin->set_config('judges',
                implode(',', [
                        $editingteacherroleid])
        );

        $this->assertCount(1, $comparisonmanager->getalljudges());

        $plugin->set_config('judges', '');

        $this->assertCount(0, $comparisonmanager->getalljudges());
    }

    public function test_canuserjudge_fakerole_assignment_submitted_team() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        groups_add_member($group, $teacher);

        $students = [];

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student1;
        groups_add_member($group, $student1);

        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student2;
        groups_add_member($group, $student2);

        $student3 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $students[] = $student3;

        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $student4 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        groups_add_member($group2, $student4);

        // Verify group assignments.
        $this->setUser($teacher);
        $assign = $this->create_instance($course, [
                'teamsubmission' => 1,
                'assignsubmission_onlinetext_enabled' => 1,
                'assignsubmission_comparativejudgement_enabled' => 1,
                'submissiondrafts' => 1,
                'requireallteammemberssubmit' => 1,
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($assign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $this->add_submission($student1, $assign);
        $this->submit_for_grading($student1, $assign);

        $this->submit_for_grading($student2, $assign);

        $comparisonmanager = new comparisonmanager($teacher->id, $assign);
        $this->assertCount(2, $comparisonmanager->getalljudges($assign));
    }
}
