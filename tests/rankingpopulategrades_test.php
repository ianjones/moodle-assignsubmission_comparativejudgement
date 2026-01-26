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

namespace assignsubmission_comparativejudgement;

use advanced_testcase;
use mod_assign_test_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');


/**
 * @group assignsubmission_comparativejudgement
 */
final class rankingpopulategrades_test extends advanced_testcase {
    use mod_assign_test_generator;

    public function test_individual_submissions(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Assignment with submissions.
        $secondassign = $this->create_instance($course, [
                'name'                                          => 'Assignment with submissions',
                'duedate'                                       => time(),
                'attemptreopenmethod'                           => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                   => 3,
                'submissiondrafts'                              => 1,
                'assignsubmission_onlinetext_enabled'           => 1,
                'assignsubmission_comparativejudgement_enabled' => 1,
                'assignfeedback_comments_enabled' => 1,
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $students = [];
        $studentsubmissions = [];
        for ($i = 0; $i < 2; $i++) {
            $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($student, $secondassign);
            $this->submit_for_grading($student, $secondassign);
            $studentsubmissions[$student->id] = $secondassign->get_user_submission($student->id, false)->id;

            $students[$student->id] = $student;
        }

        $exemplarsubmissions = [];
        for ($i = 0; $i < 2; $i++) {
            $nextuserid = exemplar::getnextuserid($secondassign);
            $submission = $secondassign->get_user_submission($nextuserid, true);
            $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            $DB->update_record('assign_submission', $submission);
            $exemplar = new exemplar();
            $exemplar->set('submissionid', $submission->id);
            $exemplar->save();
            $exemplarsubmissions[] = $submission->id;
        }

        $ranking = new ranking();

        $scores = [];
        foreach ($studentsubmissions as $submission) {
            $scores[$submission] = 50;
        }
        foreach ($exemplarsubmissions as $submission) {
            $scores[$submission] = 75;
        }

        $this->setUser($teacher);
        $ranking->saverankings(5, $secondassign->get_instance()->id, $scores);
        $ranking->populategrades($secondassign);

        $gradinginfo =
                grade_get_grades($course->id, 'mod', 'assign', $secondassign->get_instance()->id, array_keys($studentsubmissions));
        $this->assertCount(2, $gradinginfo->items[0]->grades);

        foreach (array_keys($students) as $studentid) {
            $this->assertEquals(50, $gradinginfo->items[0]->grades[$studentid]->grade);
        }
    }

    public function test_team_submissions(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Assignment with submissions.
        $secondassign = $this->create_instance($course, [
                'name'                                          => 'Assignment with submissions',
                'duedate'                                       => time(),
                'attemptreopenmethod'                           => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                   => 3,
                'submissiondrafts'                              => 1,
                'assignsubmission_onlinetext_enabled'           => 1,
                'assignsubmission_comparativejudgement_enabled' => 1,
                'teamsubmission'                                => 1,
                'requireallteammemberssubmit'                   => 1,
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $students = [];
        $groupsubmissions = [];
        $groups = [];

        for ($j = 0; $j < 2; $j++) {
            $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
            $groupstudents = [];
            for ($i = 0; $i < 2; $i++) {
                $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
                groups_add_member($group, $student);
                $students[$student->id] = $student;
                $groupstudents[] = $student->id;
            }
            $groups[$group->id] = $groupstudents;
        }

        foreach ($groups as $groupid => $groupstudents) {
            $firststudent = $students[$groupstudents[0]];
            $this->add_submission($firststudent, $secondassign);
            foreach ($groupstudents as $studentid) {
                $this->submit_for_grading($students[$studentid], $secondassign);
            }
            $groupsubmissions[$groupid] = $secondassign->get_group_submission($firststudent->id, 0, false)->id;
        }

        $exemplarsubmissions = [];
        for ($i = 0; $i < 2; $i++) {
            $nextuserid = exemplar::getnextuserid($secondassign);
            $submission = $secondassign->get_user_submission($nextuserid, true);
            $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            $DB->update_record('assign_submission', $submission);
            $exemplar = new exemplar();
            $exemplar->set('submissionid', $submission->id);
            $exemplar->save();
            $exemplarsubmissions[] = $submission->id;
        }

        $ranking = new ranking();

        $scores = [];
        foreach ($groupsubmissions as $submission) {
            $scores[$submission] = 50;
        }
        foreach ($exemplarsubmissions as $submission) {
            $scores[$submission] = 75;
        }

        $this->setUser($teacher);
        $ranking->saverankings(5, $secondassign->get_instance()->id, $scores);
        $ranking->populategrades($secondassign);

        $gradinginfo = grade_get_grades($course->id, 'mod', 'assign', $secondassign->get_instance()->id, array_keys($students));
        $this->assertCount(4, $gradinginfo->items[0]->grades);

        foreach (array_keys($students) as $studentid) {
            $this->assertEquals(50, $gradinginfo->items[0]->grades[$studentid]->grade);
        }
    }
}
