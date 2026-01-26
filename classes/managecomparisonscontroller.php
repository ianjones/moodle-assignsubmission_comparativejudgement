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

use assign_form;
use assignsubmission_comparativejudgement\event\comparison_deleted;
use html_writer;

class managecomparisonscontroller extends basecontroller {
    public function summary() {
        global $OUTPUT;

        return $OUTPUT->single_button(
            $this->getinternallink('managecomparisons'),
            get_string('managecomparisons', 'assignsubmission_comparativejudgement'),
            'get'
        );
    }

    public function view() {
        $downloadformat = optional_param('download', false, PARAM_ALPHA);

        $sort = optional_param('tsort', 'submissionid', PARAM_ALPHA);
        $table = new managecomparisonstable($this->assignment, $sort);
        $table->define_baseurl($this->getinternallink('managecomparisons'));

        if (!empty($downloadformat)) {
            $table->is_downloading($downloadformat, 'judges');
            $table->out(25, false);
            return;
        }

        $o = $this->getheader(get_string('managecomparisons', 'assignsubmission_comparativejudgement'));
        ob_start();
        $table->out(25, false);
        $contents = ob_get_contents();
        ob_end_clean();
        $o .= html_writer::tag(
            'h2',
            get_string('managecomparisonswithcount', 'assignsubmission_comparativejudgement', $table->totalrows)
        );
        $o .= $contents;
        $o .= $this->getfooter();

        return $o;
    }

    public function viewdelete() {
        global $USER;

        $comparisonid = required_param('comparisonid', PARAM_INT);

        $mform = new managecomparisondeleteform($this->getinternallink(
            'deletemanagecomparison',
            ['comparisonid' => $comparisonid]
        ), [$this->assignment, $comparisonid]);

        if ($mform->is_cancelled()) {
            redirect($this->getinternallink('managecomparisons'));
        } else if ($data = $mform->get_data()) {
            $comparison = comparison::get_record(['id' => $data->comparisonid]);
            $comparison->delete();

            comparison_deleted::create([
                'relateduserid' => $USER->id,
                'objectid'      => $this->assignment->get_course_module()->id,
                'context'       => $this->assignment->get_context(),
            ])->trigger();

            redirect($this->getinternallink('managecomparisons'));
        } else {
            $mform->set_data(['comparison' => $comparisonid]);
        }

        $o = $this->getheader(get_string('deletecomparison', 'assignsubmission_comparativejudgement'));
        $o .= $this->renderer->render(new assign_form('editsubmissionform', $mform));
        $o .= $this->getfooter();

        return $o;
    }
}
