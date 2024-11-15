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

use local_rhandler\rhandler;

defined('MOODLE_INTERNAL') || die();

/**
 * @group assignsubmission_comparativejudgement
 */
class comparisonrunrscript_test extends advanced_testcase {

    public function test_runrscript_exampledate() {
        global $CFG;

        $this->resetAfterTest();

        $exampledecisions = file_get_contents("$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/exampledecisions.csv");

        set_config('pathtorscript', '/usr/local/bin/Rscript', 'local_rhandler');

        $rhandler = new rhandler("$CFG->dirroot/mod/assign/submission/comparativejudgement/lib/pipeablescript.R");
        $rhandler->setinput($exampledecisions);
        $rhandler->execute();

        $output = $rhandler->get('output');
        $exampleoput = file_get_contents("$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/oput.csv");

        $this->assertEquals($exampleoput, $output);
    }

    public function test_runrscript_exampledate_remote() {
        global $CFG;

        if (!file_exists("$CFG->dirroot/mod/assign/submission/comparativejudgement/tests/sshproxy.php")) {
            mtrace('No sshproxy details');
            return false;
        }

        $sshproxy = '';
        require_once("$CFG->dirroot/mod/assign/submission/comparativejudgement/tests/sshproxy.php");

        $this->resetAfterTest();

        $exampledecisions = file_get_contents("$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/exampledecisions.csv");

        set_config('pathtorscript', '/usr/bin/Rscript', 'local_rhandler');
        set_config('sshproxy', $sshproxy, 'local_rhandler');

        $rhandler = new rhandler("pipeablescript.R");
        $rhandler->setinput($exampledecisions);
        $rhandler->execute();

        $output = $rhandler->get('output');
        $exampleoput = file_get_contents("$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/oput.csv");

        $this->assertEquals($exampleoput, $output);
    }
}
