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
final class comparisoncocomparison_test extends advanced_testcase {
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

        $students = [];
        $studentids = [];
        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
            $studentids[] = $students[$i]->id;
        }

        for ($j = 0; $j < 3; $j++) {
            $this->setUser($students[$j]);
            for ($i = 0; $i < 3; $i++) {
                $comparisonmanager = new comparisonmanager($students[$j]->id, $secondassign);
                $getpairtojudge1 = $comparisonmanager->getpairtojudge();
                $subs = [current($getpairtojudge1)->id, next($getpairtojudge1)->id];
                sort($subs);
                comparison::recordcomparison($secondassign->get_instance()->id, 50, $subs[0], comparison::POSITION_RIGHT, $subs[1]);
            }
        }

        $ranking = ranking::docomparison($secondassign);
        $this->assertEquals(0, $ranking->get('reliability'));

        $this->setAdminUser();
        $ranking->populategrades($secondassign);

        $grades = grade_get_grades($course->id, 'mod', 'assign', $secondassign->get_instance()->id, $studentids);
        $this->assertEquals('79.00', $grades->items[0]->grades[$students[0]->id]->str_grade);
        $this->assertEquals('72.00', $grades->items[0]->grades[$students[1]->id]->str_grade);
        $this->assertEquals('64.00', $grades->items[0]->grades[$students[2]->id]->str_grade);
        $this->assertEquals('44.00', $grades->items[0]->grades[$students[3]->id]->str_grade);
    }
}
