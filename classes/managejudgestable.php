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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class managejudgestable extends \table_sql {
    public function __construct(assign $assignment, $sortcolumn) {
        global $DB, $PAGE, $USER;

        $PAGE->requires->js_call_amd('assignsubmission_comparativejudgement/manage', 'init',
                ['entitytype' => exclusion::EXCLUSION_TYPE_JUDGE]);

        parent::__construct('managejudges_table');

        $columns = ['fullname', 'judgeid', 'comparisons', 'timetaken', 'avgtimetaken', 'mintimetaken', 'maxtimetaken',
                'sidespicked', 'first', 'last'];

        $headers = [
                get_string('fullname'),
                get_string('judgeid', 'assignsubmission_comparativejudgement'),
                get_string('noofcomparisons', 'assignsubmission_comparativejudgement'),
                get_string('timetakencomparing', 'assignsubmission_comparativejudgement'),
                get_string('avgtimetakencomparing', 'assignsubmission_comparativejudgement'),
                get_string('mintimetakencomparing', 'assignsubmission_comparativejudgement'),
                get_string('maxtimetakencomparing', 'assignsubmission_comparativejudgement'),
                get_string('sidespicked', 'assignsubmission_comparativejudgement'),
                get_string('firstcomparison', 'assignsubmission_comparativejudgement'),
                get_string('lastcomparison', 'assignsubmission_comparativejudgement'),
        ];

        if (optional_param('download', false, PARAM_ALPHA) === false) {
            $columns[] = 'include';
            $headers[] = get_string('include', 'assignsubmission_comparativejudgement');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->sort_default_column = $sortcolumn;

        $comparisonmanager = new comparisonmanager($USER->id, $assignment);
        $userids = $comparisonmanager->getalljudges();

        if ($userids) {
            list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        } else {
            $insql = " <> u.id ";
            $inparams = [];
        }
        $namefields = get_all_user_name_fields(true, 'u');

        $inparams['entitytype'] = exclusion::EXCLUSION_TYPE_JUDGE;

        $left = comparison::POSITION_LEFT;
        $right = comparison::POSITION_RIGHT;

        $this->set_count_sql("select count(id) from {user} u where u.id $insql", $inparams);
        $this->set_sql("u.id,
                            u.id as judgeid,
                            $namefields,
                            COUNT(comp.id) as comparisons,
                            SUM(comp.timetaken) as timetaken,
                            MIN(comp.timecreated) as first,
                            MAX(comp.timemodified) as last,
                            AVG(comp.timetaken) as avgtimetaken,
                            MIN(comp.timetaken) as mintimetaken,
                            MAX(comp.timetaken) as maxtimetaken,
                            CASE WHEN exclusion.id IS NULL THEN 0 ELSE 1 END as excluded,
                            SUM(CASE WHEN winningsubmissionposition = $left THEN 1 ELSE 0 END) as leftchoices,
                            SUM(CASE WHEN winningsubmissionposition = $right THEN 1 ELSE 0 END) as rightchoices",
                '{user} u
                        LEFT JOIN {assignsubmission_comp} comp ON comp.usermodified = u.id
                        LEFT JOIN {assignsubmission_exclusion} exclusion ON exclusion.entityid = u.id AND exclusion.type = :entitytype',
                "u.id $insql GROUP BY u.id, $namefields",
                $inparams);
    }

    public function col_include($row) {
        $chkname = "chk_excludeentity_$row->id";
        $attributes = ['data-entityid' => $row->id, 'class' => 'excludeentity'];

        $attributes['title'] = get_string('include', 'assignsubmission_comparativejudgement');

        return \html_writer::span(\html_writer::checkbox($chkname, $chkname, empty($row->excluded), '',
                $attributes));
    }

    public function col_avgtimetaken($row) {
        if (empty($row->avgtimetaken)) {
            return '';
        }
        return format_time($row->avgtimetaken);
    }

    public function col_sidespicked($row) {
        global $OUTPUT;
        $text = $row->leftchoices . " : " . $row->rightchoices;

        if ($row->leftchoices == $row->comparisons || $row->rightchoices == $row->comparisons) {

            if (!optional_param('download', false, PARAM_ALPHA)) {
                $text .= " " . $OUTPUT->pix_icon('i/warning', get_string('alwayssameside', 'assignsubmission_comparativejudgement'), '',
                        array('class' => 'icon icon-pre', 'title' => ''));
            }
        }

        return $text;
    }

    public function col_mintimetaken($row) {
        if (empty($row->mintimetaken)) {
            return '';
        }
        return format_time($row->mintimetaken);
    }

    public function col_maxtimetaken($row) {
        if (empty($row->maxtimetaken)) {
            return '';
        }
        return format_time($row->maxtimetaken);
    }

    public function col_timetaken($row) {
        if (empty($row->timetaken)) {
            return '';
        }
        return format_time($row->timetaken);
    }

    public function col_first($row) {
        if (empty($row->first)) {
            return '';
        }
        return userdate($row->first);
    }

    public function col_last($row) {
        if (empty($row->last)) {
            return '';
        }
        return userdate($row->last);
    }
}
