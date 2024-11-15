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

use core\persistent;
use local_rhandler\rhandler;
use stdClass;

class ranking extends persistent {
    const TABLE = 'assignsubmission_ranking';

    protected static function define_properties() {
        return [
                'assignmentid' => [
                        'type' => PARAM_INT,
                ],
                'reliability'  => [
                        'type' => PARAM_FLOAT,
                ]
        ];
    }

    public static function getrawjudgedatacsv($assignmentid) {
        global $DB;

        $sql = "select comp.id as id, comp.usermodified as judgeid,
       comp.winningsubmission as won,
       compsubs.submissionid as lost,
       comp.timetaken
from {assignsubmission_comp} comp
         inner join {assignsubmission_compsubs} compsubs on
             comp.winningsubmission <> compsubs.submissionid and comp.id = compsubs.judgementid
         inner join {assign_submission} asssubwin on asssubwin.id = comp.winningsubmission
         inner join {assign_submission} asssublose on asssublose.id = compsubs.submissionid
         LEFT JOIN {assignsubmission_exclusion} exclusion_judge ON
                exclusion_judge.entityid = comp.usermodified AND exclusion_judge.type = :entitytypejudge
         LEFT JOIN {assignsubmission_exclusion} exclusion_sub_win ON
                exclusion_sub_win.entityid = comp.winningsubmission AND exclusion_sub_win.type = :entitytypesubwin
         LEFT JOIN {assignsubmission_exclusion} exclusion_sub_lose ON
                exclusion_sub_lose.entityid = compsubs.submissionid AND exclusion_sub_lose.type = :entitytypesublose
where comp.assignmentid = :assignmentid
  AND asssubwin.status = :status
  AND asssublose.status = :status2
  AND exclusion_judge.id IS NULL AND exclusion_sub_win.id IS NULL AND exclusion_sub_lose.id IS NULL";

        $inputraw = $DB->get_records_sql($sql,
                ['assignmentid' => $assignmentid,
                 'status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
                 'status2'      => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
                 'entitytypejudge'      => exclusion::EXCLUSION_TYPE_JUDGE,
                 'entitytypesubwin'      => exclusion::EXCLUSION_TYPE_SUBMISSION,
                 'entitytypesublose'      => exclusion::EXCLUSION_TYPE_SUBMISSION]);

        if (empty($inputraw)) {
            return '';
        }

        $csv = ['JudgeID,Won,Lost,TimeTaken'];
        foreach ($inputraw as $row) {
            unset($row->id);
            $csv[] = implode(',', (array) $row);
        }
        $csv = implode("\n", $csv);

        return $csv;
    }

    public static function docomparison($assignmentid) {
        $csv = self::getrawjudgedatacsv($assignmentid);

        if (empty($csv)) {
            return false;
        }

        $rhandler = new rhandler("/mod/assign/submission/comparativejudgement/lib/pipeablescript.R");
        $rhandler->setinput($csv);
        $rhandler->execute();

        $rawoutput = $rhandler->get('output');

        if (empty($rawoutput)) {
            throw new \moodle_exception('errorexecutingscript', 'assignsubmission_comparativejudgement',
                    null, null, $rhandler->get('errors'));
        }

        $output = array_map('str_getcsv', explode("\n", $rawoutput));
        array_shift($output); // Ditch header.

        $ranking = self::get_record(['assignmentid' => $assignmentid]);
        if (!$ranking) {
            $ranking = new ranking();
        }

        $scores = [];
        foreach ($output as $row) {
            if (!isset($row) || count($row) < 2 || !(int)($row[1])) {
                continue;
            }
            $scores[$row[0]] = $row[1];
        }

        if (isset($output[0][2]) && is_numeric($output[0][2])) {
            $reliability = $output[0][2];
        } else {
            $reliability = 0;
        }
        $ranking->saverankings($reliability, $assignmentid, $scores);

        return $ranking;
    }

    public function saverankings($reliability, $assignmentid, $scores) {
        global $DB;

        $this->set('assignmentid', $assignmentid);
        $this->set('reliability', $reliability);

        $this->save();

        $DB->delete_records('assignsubmission_rankingsub', ['rankingid' => $this->get('id')]);

        foreach ($scores as $submissionid => $score) {
            $DB->insert_record('assignsubmission_rankingsub',
                    (object) ['rankingid' => $this->get('id'), 'submissionid' => $submissionid, 'score' => $score]);
        }
    }

    public function populategrades(\assign $assignment) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/gradeform.php');

        $submissiongrades = $DB->get_records_sql('SELECT asssub.id, asssub.groupid, asssub.userid, ranksub.score
                                FROM {assign_submission} asssub
                                INNER JOIN {assignsubmission_rankingsub} ranksub ON ranksub.submissionid = asssub.id
                                LEFT JOIN {assignsubmission_exemplars} exemp ON exemp.submissionid = asssub.id
                                WHERE exemp.id IS NULL AND ranksub.rankingid = :rankingid',
                ['rankingid' => $this->get('id')]
        );

        foreach ($submissiongrades as $grade) {
            // We need to save the grade for one member of the group, the assignment class takes care of the rest.

            if ($assignment->get_instance()->teamsubmission) {
                $members = $assignment->get_submission_group_members($grade->groupid, true, true);
                $userid = reset($members)->id;
            } else {
                $userid = $grade->userid;
            }

            $data = new stdClass();
            $pagination = [
                    'userid' => $grade->userid,
                    'rownum' => 0,
                    'last' => true,
                    'useridlistid' => $assignment->get_useridlist_key_id(),
                    'attemptnumber' => -1,
            ];
            $mform = new opened_mod_assign_grade_form(null, [$assignment, $data, $pagination]);

            $gradedata = (object)$mform->get_defaultdata();
            $gradedata->attemptnumber = -1;
            $gradedata->applytoall = true;
            $gradedata->grade = $grade->score;

            $assignment->save_grade($userid, $gradedata);
        }
    }
}
