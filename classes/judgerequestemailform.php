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

use moodleform;
require_once($CFG->libdir . '/formslib.php');

class judgerequestemailform extends moodleform {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'assignsubmission_comparativejudgement\\judgerequestemail';

    public function definition() {
        list($assign, $emailid) = $this->_customdata;

        $mform = $this->_form;

        $elems = [
                'action'        => 'viewpluginpage',
                'plugin'        => 'comparativejudgement',
                'pluginsubtype' => 'assignsubmission',
                'pluginaction'  => 'judgerequestemailcreate',
        ];

        foreach ($elems as $key => $val) {
            $mform->addElement('hidden', $key, $val);
            $mform->setType($key, PARAM_ALPHA);
        }

        $elems = [
                'id'            => $assign->get_course_module()->id,
                'emailid'        => $emailid,
                'assignmentid'        => $assign->get_instance()->id,
        ];

        foreach ($elems as $key => $val) {
            $mform->addElement('hidden', $key, $val);
            $mform->setType($key, PARAM_INT);
        }

        $mform->addElement('duration', 'delay', get_string('delay', 'assignsubmission_comparativejudgement'),
                ['defaultunit' => WEEKSECS, 'optional' => false]);
        $mform->setDefault('delay', 1 * DAYSECS);

        $mform->addElement('text', 'subject', get_string('subject', 'assignsubmission_comparativejudgement'), 'maxlength="100"');
        $mform->setType('subject', PARAM_TEXT);
        $mform->setDefault('subject', get_string('subjectdefault', 'assignsubmission_comparativejudgement'));

        $mform->addElement('textarea', 'body', get_string('body', 'assignsubmission_comparativejudgement'),
                ['rows' => 10,
                      'cols' => 57]);
        $mform->setType('body', PARAM_TEXT);
        $mform->addHelpButton('body', 'body', 'assignsubmission_comparativejudgement');
        $mform->setDefault('body', get_string('bodydefault', 'assignsubmission_comparativejudgement'));

        $this->add_action_buttons();
    }
}
