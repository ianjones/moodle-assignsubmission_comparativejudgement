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

class managecomparisoncommentstable extends \table_sql {
    private $cangrade;
    private $cmid;
    private $canmanageexemplars;

    /** @var exemplarcontroller */
    private $exemplarcontroller;

    public function __construct(assign $assignment, $sortcolumn) {
        global $PAGE;
        $this->exemplarcontroller = new exemplarcontroller($assignment);
        $this->canmanageexemplars =
                has_capability('assignsubmission/comparativejudgement:manageexemplars', $assignment->get_context());

        $PAGE->requires->js_call_amd('assignsubmission_comparativejudgement/manage', 'init',
                ['entitytype' => exclusion::EXCLUSION_TYPE_COMPARISONCOMMENT]);

        parent::__construct('managecomparisoncomments_table');

        $columns = ['fullname', 'submission', 'othersubmission', 'comments'];

        $headers = [
                get_string('fullname'),
                get_string('submission', 'assignsubmission_comparativejudgement'),
                get_string('comparedsubmission', 'assignsubmission_comparativejudgement'),
                get_string('comment', 'assignsubmission_comparativejudgement'),
        ];

        if (optional_param('download', false, PARAM_ALPHA) === false) {
            $columns[] = 'include';
            $headers[] = get_string('include', 'assignsubmission_comparativejudgement');
            $columns[] = 'commentpublished';
            $headers[] = get_string('commentpublished', 'assignsubmission_comparativejudgement');
        }
        $context = $assignment->get_context();
        $this->cangrade = has_capability('mod/assign:grade', $context);
        $this->cmid = $context->instanceid;

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->useridfield = 'judgeid';

        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->sort_default_column = $sortcolumn;

        $namefields = get_all_user_name_fields(true, 'u');

        $inparams = [
                'entitytype'   => exclusion::EXCLUSION_TYPE_COMPARISONCOMMENT,
                'assignmentid' => $assignment->get_instance()->id
        ];

        $this->set_sql("compsub.id,
                u.id as judgeid,
                $namefields,
                compsub.comments,
                compsub.commentsformat,
                CASE WHEN exclusion.id IS NOT NULL THEN 1 ELSE 0 END as excluded,
                asssub.userid as subuserid,
                asssub.id as submissionid,
                asssubother.userid as othersubuserid,
                compsubother.id as othersubmissionid,
                compsub.commentpublished,
                exemp.title as exemplartitle,
                exemp.id as exemplarid,
                exempother.title as otherexemplartitle,
                exempother.id as otherexemplarid",
            "{assignsubmission_compsubs} compsub
                INNER JOIN {assignsubmission_comp} comp ON compsub.judgementid = comp.id
                INNER JOIN {user} u ON u.id = comp.usermodified
                INNER JOIN {assign_submission} asssub ON asssub.id = compsub.submissionid
                LEFT JOIN {assignsubmission_exemplars} exemp ON exemp.submissionid = compsub.submissionid
                LEFT JOIN {assignsubmission_compsubs} compsubother ON compsubother.judgementid = comp.id AND compsubother.id <> compsub.id
                LEFT JOIN {assign_submission} asssubother ON asssubother.id = compsubother.submissionid
                LEFT JOIN {assignsubmission_exemplars} exempother ON exempother.submissionid = compsubother.submissionid
                LEFT JOIN {assignsubmission_exclusion} exclusion ON exclusion.entityid = compsub.id AND exclusion.type = :entitytype",
                "compsub.comments is not null AND compsub.comments <> '' AND comp.assignmentid = :assignmentid",
                $inparams);
    }

    public function col_include($row) {
        $chkname = "chk_excludeentity_$row->id";
        $attributes = ['data-entityid' => $row->id, 'class' => 'excludeentity'];

        $attributes['title'] = get_string('include', 'assignsubmission_comparativejudgement');

        return \html_writer::span(\html_writer::checkbox($chkname, $chkname, empty($row->excluded), '',
                $attributes));
    }

    public function col_othersubmission($row) {
        return $this->col_submission($row, 'othersubuserid', 'otherexemplartitle', 'otherexemplarid');
    }

    public function col_submission($row, $subuseridcol = 'subuserid', $exemplartitlecol = 'exemplartitle', $exemplaridcol = 'exemplarid') {
        if (!empty($row->$exemplartitlecol) && $this->canmanageexemplars) {
            $url = $this->exemplarcontroller->getinternallink('addexemplar');
            $url->param('exemplarid', $row->$exemplaridcol);
            return \html_writer::link($url,
                    get_string('viewexemplar', 'assignsubmission_comparativejudgement'));
        } else if ($this->cangrade && empty($row->$exemplartitlecol)) {
            return \html_writer::link(new \moodle_url('/mod/assign/view.php', [
                    'id'     => $this->cmid,
                    'rownum' => 0,
                    'action' => 'grader',
                    'userid' => $row->$subuseridcol
            ]),
                    get_string('viewassignment', 'assignsubmission_comparativejudgement'));
        } else {
            return '';
        }
    }

    public function col_comments($row) {
        return $this->format_text($row->comments, $row->commentsformat);
    }

    public function col_commentpublished($row) {
        return \html_writer::checkbox('', '', !empty($row->commentpublished), '', ['disabled' => 'disabled']);
    }
}
