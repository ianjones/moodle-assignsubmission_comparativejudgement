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
use table_sql;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class exemplartable extends table_sql {

    /** @var exemplarcontroller */
    private $exemplarcontroller;
    public function __construct(assign $assignment, $sortcolumn) {
        $this->exemplarcontroller = new exemplarcontroller($assignment);

        parent::__construct('manageexemplars_table');

        $columns = ['title', 'actions'];
        $headers = [get_string('exemplartitle', 'assignsubmission_comparativejudgement'), ''];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->sort_default_column = $sortcolumn;

        $this->set_sql("asex.id, asex.title",
                '{assignsubmission_exemplars} asex
                                            inner join {assign_submission} subs on asex.submissionid = subs.id',
                "subs.assignment = :assignmentid",
                ['assignmentid' => $assignment->get_instance()->id]);
    }

    public function col_actions($row) {
        global $OUTPUT;

        $url = $this->exemplarcontroller->getinternallink('addexemplar');
        $deleteurl = $this->exemplarcontroller->getinternallink('deleteexemplar');

        $url->param('exemplarid', $row->id);
        $deleteurl->param('exemplarid', $row->id);

        $out = '';

        $icon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $out .= $OUTPUT->action_link($url, $icon);

        $icon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
        $out .= $OUTPUT->action_link($deleteurl, $icon);

        return $out;
    }
}
