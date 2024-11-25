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

use assign;
use core\persistent;
use assignsubmission_comparativejudgement\rhandler;
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

    public static function getrawjudgedatacsv(assign $assign) {
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
            list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
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

    public static function docomparison(assign $assign) {
        $csv = self::getrawjudgedatacsv($assign);

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

        $assignmentid = $assign->get_instance()->id;
        $ranking = self::get_record(['assignmentid' => $assignmentid]);
        if (!$ranking) {
            $ranking = new ranking();
        }
        $ranking->saverankings($reliability, $assignmentid, $scores);

        return $ranking;
    }

    public static function dofakecomparison(assign $assign) {
        $assignmentid = $assign->get_instance()->id;

        $ranking = self::get_record(['assignmentid' => $assignmentid]);
        if (!$ranking) {
            $ranking = new ranking();
        }
        $csv = self::getrawjudgedatacsv($assign);

        $scores = [];
        foreach (explode("\n", $csv) as $line) {
            $cells = explode(",", $line);
            if (!is_numeric($cells[1])) {
                continue;
            }
            if (!isset($scores[$cells[1]])) {
                $scores[$cells[1]] = 0;
            }
            if (!isset($scores[$cells[2]])) {
                $scores[$cells[2]] = 0;
            }
            $scores[$cells[1]] += 1;
        }

        $ranking->saverankings(-1.4, $assignmentid, $scores);

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
