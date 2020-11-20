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

use assignsubmission_comparativejudgement\event\judgerequestemail_deleted;
use assignsubmission_comparativejudgement\event\judgerequestemail_modified;
use html_writer;

class judgerequestemailcontroller extends basecontroller {
    public function summary() {
        global $OUTPUT;

        return $OUTPUT->single_button($this->getinternallink('judgerequestemail'),
                get_string('managejudgerequestemail', 'assignsubmission_comparativejudgement'), 'get');

    }

    public function view() {
        $sort = optional_param('tsort', 'lastname, firstname', PARAM_ALPHA);
        $table = new judgerequestemailstable($this->assignment, $sort);
        $table->define_baseurl($this->getinternallink('judgerequestemail'));

        $o = $this->getheader(get_string('managejudgerequestemail', 'assignsubmission_comparativejudgement'));
        $o .= html_writer::div(get_string('managejudgerequestemailintro', 'assignsubmission_comparativejudgement'));

        ob_start();
        $table->out(25, false);
        $o .= ob_get_contents();
        ob_end_clean();

        $o .= \html_writer::link($this->getinternallink('judgerequestemailcreate'),
                get_string('newreminderemail', 'assignsubmission_comparativejudgement'), ['class' => 'btn btn-primary']);

        $o .= $this->getfooter();

        return $o;
    }

    public function viewcreateedit() {
        global $USER;

        $emailid = optional_param('emailid', null, PARAM_INT);

        $form = new judgerequestemailform($this->getinternallink('judgerequestemailcreate'), [$this->assignment, $emailid]);

        if (($data = $form->get_data())) {
            $certificationemail = new judgerequestemail($data->emailid);
            $certificationemail->set('body', $data->body);
            $certificationemail->set('subject', $data->subject);
            $certificationemail->set('delay', $data->delay);
            if (empty($data->emailid)) {
                $certificationemail->set('assignmentid', $data->assignmentid);
                $certificationemail->create();
            } else {
                $certificationemail->update();
            }

            judgerequestemail_modified::create([
                    'relateduserid' => $USER->id,
                    'objectid'      => $this->assignment->get_course_module()->id,
                    'context'       => $this->assignment->get_context()
            ])->trigger();

            redirect($this->getinternallink('judgerequestemail'));
        } else if ($form->is_cancelled()) {
            redirect($this->getinternallink('judgerequestemail'));
        }

        $email = judgerequestemail::get_record(['id' => $emailid]);
        if ($email) {
            $emailrecord = $email->to_record();
            $emailrecord->emailid = $emailid;
            unset($emailrecord->id);
            $form->set_data($emailrecord);
        }

        $o = $this->getheader(get_string('editemail', 'assignsubmission_comparativejudgement'));
        $o .= $form->render();
        $o .= $this->getfooter();

        return $o;
    }

    public function viewdelete() {
        global $USER;

        $emailid = required_param('emailid', PARAM_INT);

        $mform = new judgerequestemaildeleteform($this->getinternallink('deletejudgerequestemail',
                ['emailid' => $emailid]), [$this->assignment, $emailid]);

        if ($mform->is_cancelled()) {
            redirect($this->getinternallink('judgerequestemail'));
        } else if ($data = $mform->get_data()) {
            $email = judgerequestemail::get_record(['id' => $data->emailid]);
            $email->delete();

            judgerequestemail_deleted::create([
                    'relateduserid' => $USER->id,
                    'objectid'      => $this->assignment->get_course_module()->id,
                    'context'       => $this->assignment->get_context()
            ])->trigger();

            redirect($this->getinternallink('judgerequestemail'));
        } else {
            $mform->set_data(['email' => $emailid]);
        }

        $o = $this->getheader(get_string('deleteemail', 'assignsubmission_comparativejudgement'));
        $o .= $this->renderer->render(new \assign_form('editsubmissionform', $mform));
        $o .= $this->getfooter();

        return $o;
    }
}
