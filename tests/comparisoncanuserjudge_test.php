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
use \core_calendar\local\api as calendar_local_api;
use \core_calendar\local\event\container as calendar_event_container;

/**
 * @group assignsubmission_comparativejudgement
 */
class assignsubmission_comparativejudgement_comparisoncanuserjudge_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_canuserjudge_fakerole_assignment_submitted() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) = $this->setupstandardscenario();

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $this->add_submission($student, $secondassign);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);
        $this->assertFalse($comparisonmanager->canuserjudge());
        $this->submit_for_grading($student, $secondassign);
        $this->assertTrue($comparisonmanager->canuserjudge());
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
                'requireallteammemberssubmit' => 0,
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($assign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $this->add_submission($student1, $assign);

        $comparisonmanager1 = new comparisonmanager($student1->id, $assign);
        $comparisonmanager2 = new comparisonmanager($student2->id, $assign);
        $comparisonmanager3 = new comparisonmanager($student3->id, $assign);
        $comparisonmanager4 = new comparisonmanager($student4->id, $assign);
        $this->assertFalse($comparisonmanager1->canuserjudge());
        $this->assertFalse($comparisonmanager2->canuserjudge());

        $this->submit_for_grading($student1, $assign);

        $this->assertTrue($comparisonmanager1->canuserjudge());
        $this->assertTrue($comparisonmanager2->canuserjudge());

        $this->assertFalse($comparisonmanager3->canuserjudge());
        $this->assertFalse($comparisonmanager4->canuserjudge());
    }

    public function test_canuserjudge_fakerole_assignment_submitted_teamallsubit() {
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

        $comparisonmanager1 = new comparisonmanager($student1->id, $assign);
        $comparisonmanager2 = new comparisonmanager($student2->id, $assign);

        $this->assertFalse($comparisonmanager1->canuserjudge());
        $this->assertFalse($comparisonmanager2->canuserjudge());

        $this->submit_for_grading($student1, $assign);

        $this->assertFalse($comparisonmanager1->canuserjudge());
        $this->assertFalse($comparisonmanager2->canuserjudge());

        $this->submit_for_grading($student2, $assign);

        $this->assertTrue($comparisonmanager1->canuserjudge());
        $this->assertTrue($comparisonmanager2->canuserjudge());
    }

    public function test_canuserjudge_fakerole_gradable_users_after_cutoff() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
            $this->setupstandardscenario(['cutoffdate' => time() - 10]);
        $plugin->set_config('judgementswhileeditable', false);

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);

        $comparisonmanagerteacher = new comparisonmanager($teacher->id, $secondassign);
        $comparisonmanagerstudent = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanagerteacher->canuserjudge());
        $this->assertTrue($comparisonmanagerstudent->canuserjudge());
    }

    public function test_canuserjudge_fakerole_gradable_users_before_cutoff() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
            $this->setupstandardscenario(['cutoffdate' => time() + 10]);
        $plugin->set_config('judgementswhileeditable', false);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);

        $comparisonmanagerteacher = new comparisonmanager($teacher->id, $secondassign);
        $comparisonmanagerstudent = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanagerteacher->canuserjudge());
        $this->assertFalse($comparisonmanagerstudent->canuserjudge());
    }

    public function test_canuserjudge_fakerole_gradable_users_before_cutoff_judgementswhileeditable() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
            $this->setupstandardscenario(['cutoffdate' => time() + 10]);
        $plugin->set_config('judgementswhileeditable', true);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);

        $comparisonmanagerteacher = new comparisonmanager($teacher->id, $secondassign);
        $comparisonmanagerstudent = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanagerteacher->canuserjudge());
        $this->assertTrue($comparisonmanagerstudent->canuserjudge());
    }

    public function test_canuserjudge_role() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) = $this->setupstandardscenario();

        global $DB;
        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher' ]);
        $plugin->set_config('judges', $teacherroleid);

        $comparisonmanagerteacher = new comparisonmanager($teacher->id, $secondassign);
        $comparisonmanagereditingteacher = new comparisonmanager($editingteacher->id, $secondassign);
        $comparisonmanagerstudent = new comparisonmanager($student->id, $secondassign);
        $this->assertTrue($comparisonmanagerteacher->canuserjudge());
        $this->assertFalse($comparisonmanagerstudent->canuserjudge());
        $this->assertFalse($comparisonmanagereditingteacher->canuserjudge());

        $editingteacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher' ]);
        $plugin->set_config('judges', implode(',', [$teacherroleid, $editingteacherroleid]));

        $this->assertTrue($comparisonmanagerteacher->canuserjudge());
        $this->assertFalse($comparisonmanagerstudent->canuserjudge());
        $this->assertTrue($comparisonmanagereditingteacher->canuserjudge());
    }

    public function test_canuserjudge_starttime() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) = $this->setupstandardscenario();

        global $DB;
        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher' ]);
        $plugin->set_config('judges', $teacherroleid);
        $plugin->set_config('judgementstartdate', time() + DAYSECS);

        $comparisonmanagerteacher = new comparisonmanager($teacher->id, $secondassign);
        $comparisonmanagerstudent = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanagerteacher->canuserjudge());

        $plugin->set_config('judgementstartdate', time() - DAYSECS);

        $this->assertTrue($comparisonmanagerteacher->canuserjudge());
    }

    public function test_canuserjudge_maxjudgements() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) = $this->setupstandardscenario();

        // TO DO!
    }

    /**
     * @return array
     */
    private function setupstandardscenario($assignops = []): array {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        // Assignment with submissions.
        $arr = [
                'name'                                          => 'Assignment with submissions',
                'duedate'                                       => time(),
                'attemptreopenmethod'                           => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                   => 3,
                'submissiondrafts'                              => 1,
                'assignsubmission_onlinetext_enabled'           => 1,
                'assignsubmission_comparativejudgement_enabled' => 1,
                'assignsubmission_comparativejudgement_judgementswhileeditable' => false,
        ];
        $arr = array_merge($arr, $assignops);
        $secondassign = $this->create_instance($course, $arr);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        return array($teacher, $editingteacher, $student, $secondassign, $plugin);
    }
}
