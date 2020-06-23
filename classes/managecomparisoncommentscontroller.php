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

use assignsubmission_comparativejudgement\event\comments_imported;

class managecomparisoncommentscontroller extends basecontroller {
    public function summary() {
        global $OUTPUT;

        $commenthandler = new \assign_feedback_comments($this->assignment, 'comments');
        if (!$commenthandler->is_enabled() || empty($this->assignmentsettings->enablecomments)) {
            return '';
        }

        return $OUTPUT->single_button($this->getinternallink('managecomparisoncomments'), get_string('managecomparisoncomments', 'assignsubmission_comparativejudgement'), 'get');
    }

    public function view() {
        global $OUTPUT, $USER;

        $importcomments = optional_param('importcomments', false, PARAM_BOOL);
        if ($importcomments) {
            $manager = new managecomparisoncommentsmanager($USER->id, $this->assignment);
            $manager->importcomments();

            comments_imported::create([
                    'relateduserid' => $USER->id,
                    'objectid'      => $this->assignment->get_course_module()->id,
                    'context'       => $this->assignment->get_context()
            ])->trigger();
        }

        $downloadformat = optional_param('download', false, PARAM_ALPHA);

        $sort = optional_param('tsort', 'lastname, firstname', PARAM_ALPHA);
        $table = new managecomparisoncommentstable($this->assignment, $sort);
        $table->define_baseurl($this->getinternallink('managecomparisoncomments'));

        if (!empty($downloadformat)) {
            $table->is_downloading($downloadformat, 'comparisoncomments');
            $table->out(25, false);
            return;
        }

        $o = $this->getheader(get_string('managecomparisoncomments', 'assignsubmission_comparativejudgement'));
        ob_start();
        $table->out(25, false);
        $o .= ob_get_contents();
        ob_end_clean();

        $link = $this->getinternallink('managecomparisoncomments');
        $link->params(['importcomments' => true]);
        $o .= $OUTPUT->single_button($link,
                get_string('importcomments', 'assignsubmission_comparativejudgement'));

        $o .= $this->getfooter();

        return $o;
    }
}