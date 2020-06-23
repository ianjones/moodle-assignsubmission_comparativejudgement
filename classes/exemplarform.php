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

use moodleform;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class exemplarform extends moodleform {

    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        $mform = $this->_form;
        list($assign, $data, $submission) = $this->_customdata;

        $mform->addElement('text','title', get_string('exemplartitle', 'assignsubmission_comparativejudgement'));
        $mform->addRule('title', null, 'required', null, 'server');
        $mform->setType('title', PARAM_TEXT);

        foreach ($assign->get_submission_plugins() as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible() && $plugin->allow_submissions()) {
                $plugin->get_form_elements_for_user($submission, $mform, $data, $data->userid);
            }
        }

        $elems = [
                'id'            => $assign->get_course_module()->id,
                'userid'        => $data->userid,
                'action'        => 'viewpluginpage',
                'plugin'        => 'comparativejudgement',
                'pluginsubtype' => 'assignsubmission',
                'pluginaction'  => 'addexemplar'
        ];

        foreach ($elems as $key => $val) {
            $mform->addElement('hidden', $key, $val);
            $mform->setType($key, PARAM_ALPHA);
        }

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges', 'assign'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        if ($submission) {
            $exemplar = exemplar::get_record(['submissionid' => $submission->id]);
            $this->set_data($data->title = $exemplar->get('title'));

            if ($data) {
                $this->set_data($data);
            }
        }
    }
}

