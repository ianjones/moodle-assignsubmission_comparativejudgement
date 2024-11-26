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
class comparisonalldatespastorempty_test extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_alldatespastorempty() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
                $this->setupstandardscenario(['duedate' => time() - 10, 'cutoffdate' => time() - 5]);
        $plugin->set_config('judgementstartdate', time() - 1);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertTrue($comparisonmanager->alldatespastorempty());

        $plugin->set_config('judgementstartdate', time() + 1);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanager->alldatespastorempty());

    }

    public function test_alldatespastorempty_nojudgedate() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
                $this->setupstandardscenario(['duedate' => time() - 10, 'cutoffdate' => time() - 5]);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertTrue($comparisonmanager->alldatespastorempty());
    }

    public function test_alldatespastorempty_precutoff() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
                $this->setupstandardscenario(['duedate' => time() - 10, 'cutoffdate' => time() + 5]);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanager->alldatespastorempty());
    }

    public function test_alldatespastorempty_nocutoff() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
                $this->setupstandardscenario(['duedate' => time() + 10]);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertFalse($comparisonmanager->alldatespastorempty());

        list($teacher, $editingteacher, $student, $secondassign, $plugin) =
                $this->setupstandardscenario(['duedate' => time() - 10]);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertTrue($comparisonmanager->alldatespastorempty());
    }

    public function test_alldatespastorempty_nodates() {
        list($teacher, $editingteacher, $student, $secondassign, $plugin) = $this->setupstandardscenario(['duedate' => false]);

        $comparisonmanager = new comparisonmanager($student->id, $secondassign);

        $this->assertTrue($comparisonmanager->alldatespastorempty());
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
                'name'                                                          => 'Assignment with submissions',
                'duedate'                                                       => time(),
                'attemptreopenmethod'                                           => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                                   => 3,
                'submissiondrafts'                                              => 1,
                'assignsubmission_onlinetext_enabled'                           => 1,
                'assignsubmission_comparativejudgement_enabled'                 => 1,
                'assignsubmission_comparativejudgement_judgementswhileeditable' => false,
        ];
        $arr = array_merge($arr, $assignops);
        $secondassign = $this->create_instance($course, $arr);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        return [$teacher, $editingteacher, $student, $secondassign, $plugin];
    }
}
