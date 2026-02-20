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

/**
 * @group assignsubmission_comparativejudgement
 */
final class bradleyterry_test extends advanced_testcase {
    public function test_runrscript_exampledate(): void {
        global $CFG;

        $exampledata = $this->getcsvdata_as_objects(
            "$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/exampledecisions.csv"
        );
        $processedexampledata = bradleyterry::fitfromarray($exampledata);
        $expectedoutputfromexample = $this->getcsvdata_as_assocarray(
            "$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/exampledecisions_expectedoutput.csv"
        );

        $this->assertEquals($expectedoutputfromexample, $processedexampledata->scores);
        $this->assertEquals(0.95, $processedexampledata->reliability);
    }

    private function getcsvdata_as_objects($filename): array {
        $file = fopen($filename, "r");
        $exampledecisions = [];
        $header = fgetcsv($file, escape: "");
        while (($data = fgetcsv($file, escape: "")) !== false) {
            $exampledecisions[] = (object)array_combine($header, $data);
        }

        return $exampledecisions;
    }

    private function getcsvdata_as_assocarray($filename): array {
        $file = fopen($filename, "r");
        $exampledecisions = [];
        $header = fgetcsv($file, escape: "");
        while (($data = fgetcsv($file, escape: "")) !== false) {
            $exampledecisions[$data[0]] = $data[1];
        }

        return $exampledecisions;
    }
}
