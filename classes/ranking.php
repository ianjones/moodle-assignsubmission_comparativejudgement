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
use core\persistent;
use moodle_exception;
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
                ],
        ];
    }

    public static function getrawjudgedata(assign $assign) {
        global $DB, $USER;

        $params = ['assignmentid' => $assign->get_instance()->id,
            'status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
            'status2' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
            'entitytypejudge' => exclusion::EXCLUSION_TYPE_JUDGE,
            'entitytypesubwin' => exclusion::EXCLUSION_TYPE_SUBMISSION,
            'entitytypesublose' => exclusion::EXCLUSION_TYPE_SUBMISSION];

        $comparisonmanager = new comparisonmanager($USER->id, $assign);
        $userids = $comparisonmanager->getalljudges();
        if ($userids) {
            [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params = array_merge($params, $inparams);
        } else {
            $insql = " <> -1 ";
        }

        $sql = "select comp.id as id, comp.usermodified as judgeid,
       comp.winningsubmission as won,
       compsubs.submissionid as lost,
       comp.timetaken
from {assignsubmission_comp} comp
         inner join {assignsubmission_compsubs} compsubs on
             comp.winningsubmission <> compsubs.submissionid and comp.id = compsubs.judgementid
         inner join {assign_submission} asssubwin on asssubwin.id = comp.winningsubmission
         inner join {assign_submission} asssublose on asssublose.id = compsubs.submissionid
         LEFT JOIN {assignsubmission_exclusion} exclusion_judge
             ON exclusion_judge.entityid = comp.usermodified
             AND exclusion_judge.type = :entitytypejudge
             AND exclusion_judge.assignmentid = comp.assignmentid
         LEFT JOIN {assignsubmission_exclusion} exclusion_sub_win ON
                exclusion_sub_win.entityid = comp.winningsubmission AND exclusion_sub_win.type = :entitytypesubwin
         LEFT JOIN {assignsubmission_exclusion} exclusion_sub_lose ON
                exclusion_sub_lose.entityid = compsubs.submissionid AND exclusion_sub_lose.type = :entitytypesublose
where comp.assignmentid = :assignmentid
  AND comp.usermodified $insql
  AND asssubwin.status = :status
  AND asssublose.status = :status2
  AND exclusion_judge.id IS NULL AND exclusion_sub_win.id IS NULL AND exclusion_sub_lose.id IS NULL";

        $inputraw = $DB->get_records_sql($sql, $params);

        if (empty($inputraw)) {
            return [];
        }

        return $inputraw;
    }

    public static function docomparison(assign $assign) {
        $rawjudgedata = self::getrawjudgedata($assign);

        if (empty($rawjudgedata)) {
            return false;
        }

        // Use PHP Bradley-Terry implementation (no R dependency required).
        $result = bradleyterry::fitfromarray($rawjudgedata);
        $scores = $result->scores;
        $reliability = $result->reliability;

        $assignmentid = $assign->get_instance()->id;
        $ranking = self::get_record(['assignmentid' => $assignmentid]);
        if (!$ranking) {
            $ranking = new ranking();
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
            $DB->insert_record(
                'assignsubmission_rankingsub',
                (object) ['rankingid' => $this->get('id'), 'submissionid' => $submissionid, 'score' => $score]
            );
        }
    }

    public function populategrades(assign $assignment) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/gradeform.php');

        $submissiongrades = $DB->get_records_sql(
            'SELECT asssub.id, asssub.groupid, asssub.userid, ranksub.score
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
