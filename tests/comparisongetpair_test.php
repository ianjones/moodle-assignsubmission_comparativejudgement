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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
require_once($CFG->dirroot . '/mod/assign/submission/comparativejudgement/locallib.php');

use advanced_testcase;
use mod_assign_test_generator;

/**
 * @group assignsubmission_comparativejudgement
 */
final class comparisongetpair_test extends advanced_testcase {
    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_canuserjudge_fakerole_assignment_do_comparisons(): void {
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
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $students = [];
        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }

        $this->setUser($students[0]);

        $comparisonmanager = new comparisonmanager($students[0]->id, $secondassign);
        $getpairtojudge1 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge1);
        $this->assertCount(2, $comparisonmanager->getpairtojudge(true));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge1)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge1)->id
        );

        $getpairtojudge2 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge2);
        $this->assertCount(1, array_intersect_key($getpairtojudge1, $getpairtojudge2));

        $this->assertFalse($comparisonmanager->getpairtojudge(true));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge2)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge2)->id
        );

        $getpairtojudge3 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge3);
        $this->assertCount(1, array_intersect_key($getpairtojudge3, $getpairtojudge2));
        $this->assertCount(1, array_intersect_key($getpairtojudge3, $getpairtojudge1));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge3)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge3)->id
        );

        $getpairtojudge = $comparisonmanager->getpairtojudge();
        $this->assertFalse($getpairtojudge);
    }

    public function test_canuserjudge_fakerole_assignment_do_infinite_comparisons(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Assignment with submissions.
        $secondassign = $this->create_instance($course, [
                'name'                                                    => 'Assignment with submissions',
                'duedate'                                                 => time(),
                'attemptreopenmethod'                                     => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                             => 3,
                'submissiondrafts'                                        => 1,
                'assignsubmission_onlinetext_enabled'                     => 1,
                'assignsubmission_comparativejudgement_enabled'           => 1,
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);
        $plugin->set_config('allowrepeatcomparisons', true);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $students = [];
        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }

        $this->setUser($students[0]);

        $comparisonmanager = new comparisonmanager($students[0]->id, $secondassign);
        $comparisonmanager->disablerandomness();
        $getpairtojudge1 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge1);
        $this->assertCount(2, $comparisonmanager->getpairtojudge(true));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge1)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge1)->id
        );

        $getpairtojudge2 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge2);
        $this->assertCount(1, array_intersect_key($getpairtojudge1, $getpairtojudge2));

        $this->assertFalse($comparisonmanager->getpairtojudge(true));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge2)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge2)->id
        );

        $getpairtojudge3 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge3);
        $this->assertCount(1, array_intersect_key($getpairtojudge3, $getpairtojudge2));
        $this->assertCount(1, array_intersect_key($getpairtojudge3, $getpairtojudge1));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge3)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge3)->id
        );

        $getpairtojudge4 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge4);
        $this->assertCount(2, array_intersect_key($getpairtojudge1, $getpairtojudge4));

        comparison::recordcomparison(
            $secondassign->get_instance()->id,
            50,
            current($getpairtojudge4)->id,
            comparison::POSITION_RIGHT,
            next($getpairtojudge4)->id
        );

        $getpairtojudge5 = $comparisonmanager->getpairtojudge();
        $this->assertCount(2, $getpairtojudge5);
        $this->assertCount(2, array_intersect_key($getpairtojudge2, $getpairtojudge5));
    }

    public function test_canuserjudge_fakerole_assignment_do_loads_of_comparisons(): void {
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
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $students = [];
        for ($i = 0; $i < 10; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }

        $compared = [];
        for ($i = 0; $i < 10; $i++) {
            $student = $students[$i];

            $compared[$i] = [];
            $this->setUser($student);
            $comparisonmanager = new comparisonmanager($student->id, $secondassign);
            do {
                $getpairtojudge = $comparisonmanager->getpairtojudge();

                if ($getpairtojudge) {
                    comparison::recordcomparison(
                        $secondassign->get_instance()->id,
                        50,
                        current($getpairtojudge)->id,
                        comparison::POSITION_RIGHT,
                        next($getpairtojudge)->id
                    );

                    $akeys = array_keys($getpairtojudge);
                    sort($akeys);
                    $key = implode('|', $akeys);
                    $this->assertNotContains($key, $compared);
                    $compared[$i][] = $key;
                }
            } while (!empty($getpairtojudge));

            $this->assertCount(36, $compared[$i]);

            if ($i > 0) {
                $this->assertNotEquals(
                    array_slice($compared[$i], 0, 3),
                    array_slice($compared[$i - 1], 0, 3)
                );
            }
        }
    }

    public function test_canuserjudge_fakerole_assignment_do_comparisons_exemplar_rand(): void {
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
        ]);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $students = [];
        $studentsubmissions = [];
        for ($i = 0; $i < 8; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
            $studentsubmissions[] = $secondassign->get_user_submission($students[$i]->id, false)->id;
        }
        // Add exemplars.
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

        $compared = [];
        $this->setUser($students[0]);
        $comparisonmanager = new comparisonmanager($students[0]->id, $secondassign);
        do {
            $getpairtojudge = $comparisonmanager->getpairtojudge();

            if ($getpairtojudge) {
                comparison::recordcomparison(
                    $secondassign->get_instance()->id,
                    50,
                    current($getpairtojudge)->id,
                    comparison::POSITION_RIGHT,
                    next($getpairtojudge)->id
                );

                $akeys = array_keys($getpairtojudge);
                sort($akeys);
                $key = implode('|', $akeys);
                $this->assertNotContains($key, $compared);
                $compared[] = $key;
            }
        } while (!empty($getpairtojudge));

        $this->assertCount(35, $compared);

        $compared = [];
        $this->setUser($students[1]);
        $comparisonmanager = new comparisonmanager($students[1]->id, $secondassign);
        do {
            $getpairtojudge = $comparisonmanager->getpairtojudge();

            if ($getpairtojudge) {
                comparison::recordcomparison(
                    $secondassign->get_instance()->id,
                    50,
                    current($getpairtojudge)->id,
                    comparison::POSITION_RIGHT,
                    next($getpairtojudge)->id
                );

                $akeys = array_keys($getpairtojudge);
                sort($akeys);
                $key = implode('|', $akeys);
                $this->assertNotContains($key, $compared);
                $compared[] = $key;
            }
        } while (!empty($getpairtojudge));

        $this->assertCount(35, $compared);
    }
}
