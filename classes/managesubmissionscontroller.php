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

use assignsubmission_comparativejudgement\event\grades_calculated;
use assignsubmission_comparativejudgement\event\grades_imported;
use html_writer;

class managesubmissionscontroller extends basecontroller {
    public function summary() {
        global $OUTPUT;

        return $OUTPUT->single_button($this->getinternallink('managesubmissions'),
                get_string('managesubmissions', 'assignsubmission_comparativejudgement'), 'get');
    }

    public function view() {
        global $PAGE, $OUTPUT, $USER;

        $url = $this->getinternallink('managesubmissions');
        $assignmentid = $this->assignment->get_instance()->id;

        if (optional_param('doranking', false, PARAM_BOOL)) {
            ranking::docomparison($assignmentid);

            grades_calculated::create([
                    'relateduserid' => $USER->id,
                    'objectid'      => $this->assignment->get_course_module()->id,
                    'context'       => $this->assignment->get_context()
            ])->trigger();
        }

        if (optional_param('downloadrawjudgedata', false, PARAM_BOOL)) {
            $csv = ranking::getrawjudgedatacsv($assignmentid);
            send_file($csv, "rawjudgedata_$assignmentid.csv", 0, 0, true, true);
        }

        if (
                has_capability('mod/assign:grade', $this->assignment->get_context())
                &&
                optional_param('copytogradebook', false, PARAM_BOOL)
        ) {
            $ranking = ranking::get_record(['assignmentid' => $assignmentid]);
            $ranking->populategrades($this->assignment);

            grades_imported::create([
                    'relateduserid' => $USER->id,
                    'objectid'      => $this->assignment->get_course_module()->id,
                    'context'       => $this->assignment->get_context()
            ])->trigger();
        }

        $sort = optional_param('tsort', 'lastname, firstname', PARAM_ALPHA);
        $table = new managesubmissionstable($this->assignment, $sort);
        $table->define_baseurl($url);

        $downloadformat = optional_param('download', false, PARAM_ALPHA);
        if (!empty($downloadformat)) {
            $table->is_downloading($downloadformat, 'submissions');
            $table->out(25, false);
            return;
        }

        $PAGE->set_url($url);

        $o = $this->getheader(get_string('managesubmissions', 'assignsubmission_comparativejudgement'));
        ob_start();
        $table->out(25, false);
        $o .= ob_get_contents();
        ob_end_clean();

        $ranking = ranking::get_record(['assignmentid' => $assignmentid]);
        if ($ranking) {
            $o .= html_writer::div(get_string('lastcalculation', 'assignsubmission_comparativejudgement',
                    userdate($ranking->get('timemodified'))));
            $o .= html_writer::div(get_string('lastreliability', 'assignsubmission_comparativejudgement',
                    $ranking->get('reliability')));
        }

        $link = $this->getinternallink('managesubmissions', ['doranking' => true]);
        $o .= $OUTPUT->single_button($link,
                get_string('calculategrades', 'assignsubmission_comparativejudgement'));

        $link = $this->getinternallink('managesubmissions', ['downloadrawjudgedata' => true]);
        $o .= $OUTPUT->single_button($link,
                get_string('downloadrawjudgedata', 'assignsubmission_comparativejudgement'));

        if (has_capability('mod/assign:grade', $this->assignment->get_context())) {
            $link = $this->getinternallink('managesubmissions', ['copytogradebook' => true]);
            $o .= $OUTPUT->single_button($link,
                    get_string('copytogradebook', 'assignsubmission_comparativejudgement'));
        }

        $o .= $this->getfooter();

        return $o;
    }
}
