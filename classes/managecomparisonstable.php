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
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class managecomparisonstable extends \table_sql {
    private $cangrade;
    private $canmanageexemplars;
    private $cmid;
    /** @var managecomparisonscontroller */
    private $managecomparisonscontroller;

    private bool $teamsubmission;

    public function __construct(assign $assignment, $sortcolumn) {
        global $PAGE;
        $PAGE->requires->js_call_amd('assignsubmission_comparativejudgement/manage', 'init',
            ['assignmentid' => $assignment->get_instance()->id, 'entitytype' => exclusion::EXCLUSION_TYPE_SUBMISSION]);

        parent::__construct('managecomparisons_table');

        $this->useridfield = 'userid';
        $context = $assignment->get_context();
        $this->cangrade = has_capability('mod/assign:grade', $context);
        $this->cmid = $context->instanceid;
        $this->managecomparisonscontroller = new managecomparisonscontroller($assignment);
        $this->canmanageexemplars =
            has_capability('assignsubmission/comparativejudgement:manageexemplars', $assignment->get_context());

        $this->teamsubmission = $assignment->get_instance()->teamsubmission;

        $columns =
            ['fullname', 'winsubmission', 'winsubmissionid', 'loosesubmission', 'loosesubmissionid', 'timetaken',
                'winningsubmissionposition', 'actions'];
        $headers = [
            get_string('fullname'),
            get_string('winningsubmission', 'assignsubmission_comparativejudgement'),
            get_string('winningsubmissionid', 'assignsubmission_comparativejudgement'),
            get_string('loosingsubmission', 'assignsubmission_comparativejudgement'),
            get_string('loosingsubmissionid', 'assignsubmission_comparativejudgement'),
            get_string('timetaken', 'assignsubmission_comparativejudgement'),
            get_string('winningposition', 'assignsubmission_comparativejudgement'),
            '',
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->sort_default_column = $sortcolumn;

        $namefields = fields::for_name()->get_sql('ujudge')->selects;
        if ($this->teamsubmission) {
            $this->set_sql("comp.id, ujudge.id as userid $namefields,
                                asssubwin.id as winsubmissionid, exempwin.title as winexemplartitle, exempwin.id as winexemplarid, gwin.name as wingroupname,
                                asssubloose.id as loosesubmissionid, exemploose.title as looseexemplartitle, exemploose.id as looseexemplarid, gloose.name as loosegroupname,
                                comp.timetaken, comp.winningsubmissionposition",
                '{assignsubmission_comp} comp
                            INNER JOIN {user} ujudge ON ujudge.id = comp.usermodified
                            INNER JOIN {assignsubmission_compsubs} compsubs ON compsubs.judgementid = comp.id and compsubs.submissionid <> comp.winningsubmission
                            INNER JOIN {assign_submission} asssubwin ON asssub.id = comp.winningsubmission
                            INNER JOIN {assign_submission} asssubloose ON asssubloose.id = compsubs.submissionid
                            LEFT JOIN {assignsubmission_exemplars} exempwin ON exempwin.submissionid = asssubwin.id
                            LEFT JOIN {assignsubmission_exemplars} exemploose ON exemploose.submissionid = asssubloose.id
                            LEFT JOIN {groups} gwin ON gwin.id = asssubwin.groupid
                            LEFT JOIN {groups} gloose ON gloose.id = asssubloose.groupid',
                "comp.assignmentid = :assignmentid",
                ['assignmentid' => $assignment->get_instance()->id]);
        } else {
            $winnamefields = fields::for_name()->get_sql('uwin', true, 'win')->selects;
            $loosefields = fields::for_name()->get_sql('uloose', true, 'loose')->selects;
            $this->set_sql("comp.id, ujudge.id as userid $namefields,
                                asssubwin.id as winsubmissionid, exempwin.title as winexemplartitle, exempwin.id as winexemplarid $winnamefields, uwin.id as winuserid,
                                asssubloose.id as loosesubmissionid, exemploose.title as looseexemplartitle, exemploose.id as looseexemplarid $loosefields, uwin.id as looseuserid,
                                comp.timetaken, comp.winningsubmissionposition",
                '{assignsubmission_comp} comp
                            INNER JOIN {user} ujudge ON ujudge.id = comp.usermodified
                            INNER JOIN {assignsubmission_compsubs} compsubs ON compsubs.judgementid = comp.id and compsubs.submissionid <> comp.winningsubmission
                            INNER JOIN {assign_submission} asssubwin ON asssubwin.id = comp.winningsubmission
                            INNER JOIN {assign_submission} asssubloose ON asssubloose.id = compsubs.submissionid
                            LEFT JOIN {assignsubmission_exemplars} exempwin ON exempwin.submissionid = asssubwin.id
                            LEFT JOIN {assignsubmission_exemplars} exemploose ON exemploose.submissionid = asssubloose.id
                            LEFT JOIN {user} uwin ON uwin.id = asssubwin.userid
                            LEFT JOIN {user} uloose ON uloose.id = asssubloose.userid',
                "comp.assignmentid = :assignmentid",
                ['assignmentid' => $assignment->get_instance()->id]);
        }
    }

    public function col_winsubmission($row) {
        return $this->submissioncolumn($row, 'win');
    }

    public function col_loosesubmission($row) {
        return $this->submissioncolumn($row, 'loose');
    }

    public function col_timetaken($row) {
        return format_time($row->timetaken);
    }

    public function col_winningsubmissionposition($row) {
        if ($row->winningsubmissionposition == 1) {
            return get_string('left', 'assignsubmission_comparativejudgement');
        }
        if ($row->winningsubmissionposition == 2) {
            return get_string('right', 'assignsubmission_comparativejudgement');
        }
    }

    public function col_actions($row) {
        global $OUTPUT;

        $out = '';

        $icon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
        $out .= $OUTPUT->action_link($this->managecomparisonscontroller->getinternallink('deletecomparison', ['comparisonid' => $row->id]), $icon);

        return $out;
    }

    /**
     * @param $exemplartitle
     * @param $exemplarid
     * @param $userid
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function submissioncolumn($rawrow, $type): string {
        $row = new stdClass();
        foreach ((array)$rawrow as $key => $value) {
            if (strpos($key, $type) === 0) {
                $row->{substr($key, strlen($type))} = $value;
            }
        }

        if (!empty($row->exemplartitle)) {
            if ($this->canmanageexemplars) {
                $url = $this->managecomparisonscontroller->getinternallink('addexemplar');
                $url->param('exemplarid', $row->exemplarid);
                return html_writer::link($url, $row->exemplartitle);
            } else {
                return $row->exemplartitle;
            }
        }

        if ($this->teamsubmission) {
            if (empty($row->groupname)) {
                $label = get_string('defaultteam', 'mod_assign');
            } else {
                $label = $row->groupname;
            }
        } else {
            $label = fullname($row);
        }

        if ($this->cangrade) {
            return html_writer::link(new \moodle_url('/mod/assign/view.php', [
                'id' => $this->cmid,
                'rownum' => 0,
                'action' => 'grader',
                'userid' => $row->userid,
            ]),
                $label);
        } else {
            return $label;
        }
    }
}
