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

use assign;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class judgerequestemailstable extends \table_sql {
    private $controller;

    public function __construct(assign $assignment, $sortcolumn) {
        global $USER;

        parent::__construct('judgerequestemailstable_table');

        $this->define_columns(['delay', 'subject', 'actions']);
        $this->define_headers([
                get_string('delay', 'assignsubmission_comparativejudgement'),
                get_string('subject', 'assignsubmission_comparativejudgement'),
                '',
        ]);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->sort_default_column = $sortcolumn;

        $this->set_count_sql('SELECT COUNT(id) FROM {assignsubmission_email}');
        $this->set_sql('id, delay, subject',
                '{assignsubmission_email}',
                'assignmentid = :assignmentid',
                ['assignmentid' => $assignment->get_instance()->id]);

        $this->controller = new judgerequestemailcontroller($assignment);
        $this->manager = new comparisonmanager($USER->id, $assignment);
    }

    public function col_delay($row) {
        $delay = format_time($row->delay);
        $lasttime = $this->manager->getlastdate();

        if ($lasttime == false) {
            $lasttime = get_string('never', 'assignsubmission_comparativejudgement');
        } else {
            $lasttime = userdate($lasttime + $row->delay);
        }

        return get_string('delaydetail', 'assignsubmission_comparativejudgement', (object) [
                'delay' => $delay, 'current' => $lasttime,
        ]);
    }

    public function col_actions($row) {
        global $OUTPUT;

        $out = '';

        $icon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $out .= $OUTPUT->action_link($this->controller->getinternallink('judgerequestemailcreate', ['emailid' => $row->id]), $icon);

        $icon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
        $out .= $OUTPUT->action_link($this->controller->getinternallink('deletejudgerequestemail', ['emailid' => $row->id]), $icon);

        return $out;
    }
}
