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

require_once($CFG->libdir . '/formslib.php');

use moodleform;

class comparisonform extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $hidden = ['winner', 'loser', 'position', 'starttime'];
        foreach ($hidden as $fieldname) {
            $mform->addElement('hidden', $fieldname);
            $mform->setType($fieldname, PARAM_INT);
        }

        $mform->addElement('editor', 'comments_winner_' . $this->_customdata['position'],
                get_string('comments', 'assignsubmission_comparativejudgement'), null, ['autosave' => false]);

        $mform->addElement('editor', 'comments_loser_' . $this->_customdata['position'],
                get_string('comments', 'assignsubmission_comparativejudgement'), null, ['autosave' => false]);

        if ($this->_customdata['position'] == comparison::POSITION_LEFT) {
            $mform->addElement('submit', 'buttonleft', get_string('left', 'assignsubmission_comparativejudgement'));
        } else if ($this->_customdata['position'] == comparison::POSITION_RIGHT) {
            $mform->addElement('submit', 'buttonright', get_string('right', 'assignsubmission_comparativejudgement'));
        }
    }
}