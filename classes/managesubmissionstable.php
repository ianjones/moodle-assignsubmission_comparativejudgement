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
use core_user\fields;
use html_writer;
use moodle_url;
use table_sql;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class managesubmissionstable extends table_sql {
    private $cangrade;
    private $canmanageexemplars;
    private $cmid;
    /** @var exemplarcontroller */
    private $exemplarcontroller;

    public function __construct(assign $assignment, $sortcolumn) {
        global $PAGE;
        $PAGE->requires->js_call_amd(
            'assignsubmission_comparativejudgement/manage',
            'init',
            ['assignmentid' => $assignment->get_instance()->id, 'entitytype' => exclusion::EXCLUSION_TYPE_SUBMISSION]
        );

        parent::__construct('managesubmissions_table');

        $this->useridfield = 'userid';
        $context = $assignment->get_context();
        $this->cangrade = has_capability('mod/assign:grade', $context);
        $this->cmid = $context->instanceid;
        $this->exemplarcontroller = new exemplarcontroller($assignment);
        $this->canmanageexemplars =
                has_capability('assignsubmission/comparativejudgement:manageexemplars', $assignment->get_context());

        $teamsubmission = $assignment->get_instance()->teamsubmission;
        if ($teamsubmission) {
            $firstcol = 'groupname';
            $firstcollabel = get_string('group');
        } else {
            $firstcol = 'fullname';
            $firstcollabel = get_string('fullname');
        }

        $columns =
                [$firstcol, 'submission', 'submissionid', 'comparisons', 'timetaken',
                    'avgtimetaken', 'first', 'last', 'wins', 'losses', 'score'];
        $headers = [
                $firstcollabel,
                get_string('submission', 'assignsubmission_comparativejudgement'),
                get_string('submissionid', 'assignsubmission_comparativejudgement'),
                get_string('noofcomparisonsreceived', 'assignsubmission_comparativejudgement'),
                get_string('timetakencomparing', 'assignsubmission_comparativejudgement'),
                get_string('avgtimetakencomparing', 'assignsubmission_comparativejudgement'),
                get_string('firstcomparison', 'assignsubmission_comparativejudgement'),
                get_string('lastcomparison', 'assignsubmission_comparativejudgement'),
                get_string('wins', 'assignsubmission_comparativejudgement'),
                get_string('losses', 'assignsubmission_comparativejudgement'),
                get_string('score', 'assignsubmission_comparativejudgement'),
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

        if ($teamsubmission) {
            $this->set_count_sql(
                "select count(id) from {assign_submission} where status = :status and " .
                    "assignment = :assignment and groupid <> 0 and userid = 0",
                ['status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED, 'assignment' => $assignment->get_instance()->id]
            );

            $this->set_sql(
                "asssub.id, asssub.id as submissionid,
                            max(asssub.userid) as userid, asssub.id as entityid, g.name as groupname,
                            exemp.title as exemplartitle, exemp.id as exemplarid, count(comp.id) as comparisons,
                            sum(comp.timetaken) as timetaken, avg(comp.timetaken) as avgtimetaken,
                            MIN(comp.timecreated) as first, MAX(comp.timemodified) as last,
                            SUM(CASE WHEN asssub.id = comp.winningsubmission THEN 1 ELSE 0 END) as wins,
                            SUM(CASE WHEN asssub.id <> comp.winningsubmission THEN 1 ELSE 0 END) as losses,
                            rsub.score,
                            CASE WHEN exclusion.id IS NOT NULL THEN 1 ELSE 0 END as excluded",
                '{assign_submission} asssub
                            LEFT JOIN {assignsubmission_exemplars} exemp ON exemp.submissionid = asssub.id
                            LEFT JOIN {groups} g ON g.id = asssub.groupid
                            LEFT JOIN {assignsubmission_compsubs} compsubs ON compsubs.submissionid = asssub.id
                            LEFT JOIN {assignsubmission_comp} comp ON comp.id = compsubs.judgementid
                            LEFT JOIN {assignsubmission_rankingsub} rsub ON rsub.submissionid = asssub.id
                            LEFT JOIN {assignsubmission_exclusion} exclusion ON exclusion.entityid = asssub.id AND
                                exclusion.type = :entitytype',
                "asssub.assignment = :assignmentid AND asssub.status = :status AND
                        asssub.userid <= 0 GROUP BY asssub.id, rsub.score, exclusion.id,
                        asssub.id, g.name, g.id, exemp.title",
                ['assignmentid' => $assignment->get_instance()->id, 'status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
                'entitytype'   => exclusion::EXCLUSION_TYPE_SUBMISSION]
            );
        } else {
            $this->set_count_sql(
                "select count(id) from {assign_submission} where status = :status and " .
                    "assignment = :assignment and groupid = 0 and userid <> 0",
                ['status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED, 'assignment' => $assignment->get_instance()->id]
            );

            $namefields = fields::for_name()->get_sql('u')->selects;
            $this->set_sql(
                "asssub.id, u.id as userid, asssub.id as entityid $namefields, asssub.id as submissionid, " .
                           "exemp.title as exemplartitle,
                                exemp.id as exemplarid, COUNT(comp.id) as comparisons, SUM(comp.timetaken) as timetaken,
                                AVG(comp.timetaken) as avgtimetaken,
                                MIN(comp.timecreated) as first, MAX(comp.timemodified) as last,
                                SUM(CASE WHEN asssub.id = comp.winningsubmission THEN 1 ELSE 0 END) as wins,
                                SUM(CASE WHEN asssub.id <> comp.winningsubmission THEN 1 ELSE 0 END) as losses,
                                rsub.score,
                                CASE WHEN exclusion.id IS NOT NULL THEN 1 ELSE 0 END as excluded",
                '{assign_submission} asssub
                            LEFT JOIN {assignsubmission_exemplars} exemp ON exemp.submissionid = asssub.id
                            LEFT JOIN {user} u ON u.id = asssub.userid
                            LEFT JOIN {assignsubmission_compsubs} compsubs ON compsubs.submissionid = asssub.id
                            LEFT JOIN {assignsubmission_comp} comp ON comp.id = compsubs.judgementid
                            LEFT JOIN {assignsubmission_rankingsub} rsub ON rsub.submissionid = asssub.id
                            LEFT JOIN {assignsubmission_exclusion} exclusion ON
                            exclusion.entityid = asssub.id AND exclusion.type = :entitytype',
                "asssub.assignment = :assignmentid AND asssub.status = :status AND asssub.userid <> 0 GROUP BY u.id,
                    exemp.title, exemp.id, exclusion.id, rsub.score, asssub.id $namefields",
                ['assignmentid' => $assignment->get_instance()->id, 'status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
                'entitytype'   => exclusion::EXCLUSION_TYPE_SUBMISSION]
            );
        }
    }

    public function col_include($row) {
        $chkname = "chk_excludeentity_$row->entityid";
        $attributes = ['data-entityid' => $row->entityid, 'class' => 'excludeentity'];

        $attributes['title'] = get_string('include', 'assignsubmission_comparativejudgement');

        return html_writer::span(html_writer::checkbox(
            $chkname,
            $chkname,
            empty($row->excluded),
            '',
            $attributes
        ));
    }

    public function col_groupname($row) {
        if (!empty($row->exemplartitle)) {
            return $row->exemplartitle;
        }
        if (!empty($row->groupname)) {
            return $row->groupname;
        }

        return get_string('defaultteam', 'mod_assign');
    }

    public function col_fullname($row) {
        if (!empty($row->exemplartitle)) {
            return $row->exemplartitle;
        } else {
            return parent::col_fullname($row);
        }
    }

    public function col_submission($row) {
        if (!empty($row->exemplartitle) && $this->canmanageexemplars) {
            $url = $this->exemplarcontroller->getinternallink('addexemplar');
            $url->param('exemplarid', $row->exemplarid);
            return html_writer::link(
                $url,
                get_string('viewexemplar', 'assignsubmission_comparativejudgement')
            );
        } else if ($this->cangrade) {
            return html_writer::link(
                new moodle_url('/mod/assign/view.php', [
                    'id'     => $this->cmid,
                    'rownum' => 0,
                    'action' => 'grader',
                    'userid' => $row->userid,
                ]),
                get_string('viewassignment', 'assignsubmission_comparativejudgement')
            );
        } else {
            return '';
        }
    }

    public function col_avgtimetaken($row) {
        if (empty($row->avgtimetaken)) {
            return '';
        }
        return format_time($row->avgtimetaken);
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
