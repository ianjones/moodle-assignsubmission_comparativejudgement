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

class exemplarcontroller extends basecontroller {
    public function summary() {
        global $OUTPUT;
        return $OUTPUT->single_button($this->getinternallink('manageexemplars'),
                get_string('manageexemplars', 'assignsubmission_comparativejudgement'), 'get');
    }

    public function viewdelete() {
        $exemplarid = required_param('exemplarid', PARAM_INT);

        $mform = new exemplardeleteform($this->getinternallink('deleteexemplar'));

        if ($mform->is_cancelled()) {
            redirect($this->getinternallink('manageexemplars'));
        } else if ($data = $mform->get_data()) {
            $exemplar = exemplar::get_record(['id' => $data->exemplarid]);
            $exemplar->delete_exemplar_submission($this->assignment);
            redirect($this->getinternallink('manageexemplars'));
        } else {
            $mform->set_data(['exemplarid' => $exemplarid]);
        }

        $o = $this->getheader(get_string('deleteexemplar', 'assignsubmission_comparativejudgement'));
        $o .= $this->renderer->render(new \assign_form('editsubmissionform', $mform));
        $o .= $this->getfooter();

        return $o;
    }

    public function viewmanageexemplars() {
        global $OUTPUT;

        $sort = optional_param('tsort', 'lastname, firstname', PARAM_ALPHA);
        $table = new exemplartable($this->assignment, $sort);
        $table->define_baseurl($this->getinternallink('manageexemplars'));

        $o = $this->getheader(get_string('manageexemplars', 'assignsubmission_comparativejudgement'));
        ob_start();
        $table->out(25, false);
        $o .= ob_get_contents();
        ob_end_clean();

        $o .= $OUTPUT->single_button($this->getinternallink('addexemplar'),
                get_string('addexemplar', 'assignsubmission_comparativejudgement'));

        $o .= $this->getfooter();

        return $o;
    }

    public function view() {
        global $PAGE, $DB;

        $exemplarid = optional_param('exemplarid', false, PARAM_INT);
        if ($exemplarid) {
            $exemplar = exemplar::get_record(['id' => $exemplarid]);
            $submission = $DB->get_record('assign_submission', ['id' => $exemplar->get('submissionid'), 'latest' => 1]);

            $data = new \stdClass();
            $data->userid = $submission->userid;
        } else {
            $submission = null;

            $data = new \stdClass();
            $data->userid = exemplar::getnextuserid($this->assignment);
        }
        $url = $this->getinternallink('addexemplar');
        $url->param('exemplarid', $exemplarid);
        $PAGE->set_url($url);

        $mform = new exemplarform($url, [$this->assignment, $data, $submission]);

        if ($mform->is_cancelled()) {
            redirect($this->getinternallink('manageexemplars'));
        } else if ($data = $mform->get_data()) {
            exemplar::save_exemplar_submission($data, $this->assignment, $submission, $notices);
            // Do something with notices.
            redirect($this->getinternallink('manageexemplars'));
        }

        $o = $this->getheader(get_string('editexemplar', 'assignsubmission_comparativejudgement'));
        $o .= $this->renderer->render(new \assign_form('editsubmissionform', $mform));
        $o .= $this->getfooter();

        return $o;
    }
}
