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
use assign_feedback_comments;
use stdClass;

class managecomparisoncommentsmanager {
    private $assignment;
    private $assignmentinstance;

    public function __construct($userid, assign $assignment) {
        $this->assignment = $assignment;
        $this->assignmentinstance = $this->assignment->get_instance($userid);
    }

    public function importcomments() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $uniquecol = $DB->sql_concat('comp.id', '"_"', 'sub.id');
        $commentssql = "SELECT $uniquecol, comp.id as compsubid, sub.*, comp.comments, comp.commentsformat
                        FROM {assignsubmission_compsubs} comp
                            INNER JOIN {assign_submission} sub ON sub.id = comp.submissionid
                            LEFT JOIN {assignsubmission_exclusion} exclusion ON exclusion.entityid = comp.id AND
                            exclusion.type = :entitytype
                        WHERE comp.comments is not null AND comp.comments <> '' AND sub.assignment = :assignmentid
                            AND commentpublished = 0 AND exclusion.id IS NULL";
        $comments = $DB->get_records_sql($commentssql,
            ['assignmentid' => $this->assignmentinstance->id, 'entitytype' => exclusion::EXCLUSION_TYPE_COMPARISONCOMMENT]);

        $commenthandler = new assign_feedback_comments($this->assignment, 'comments');

        $formattedcommentsbysubmissionid = [];
        $compsubids = [];
        foreach ($comments as $commentsubmission) {
            if (!isset($formattedcommentsbysubmissionid[$commentsubmission->id])) {
                $formattedcommentsbysubmissionid[$commentsubmission->id] = ['submission' => $commentsubmission, 'comments' => ''];
            }
            $formattedcommentsbysubmissionid[$commentsubmission->id]['comments'] .=
                format_text($commentsubmission->comments, $commentsubmission->commentsformat);

            $compsubids[] = $commentsubmission->compsubid;
        }

        foreach ($formattedcommentsbysubmissionid as $info) {
            $commentsubmission = $info['submission'];
            if ($this->assignment->get_instance()->teamsubmission) {
                $members = array_keys($this->assignment->get_submission_group_members($commentsubmission->groupid, true, true));
            } else {
                $members = [$commentsubmission->userid];
            }

            foreach ($members as $member) {
                $grade = $this->assignment->get_user_grade($member, true);
                $feedbackcomment = $commenthandler->get_feedback_comments($grade->id);

                if ($feedbackcomment) {
                    $feedbackcomment->commenttext .= $info['comments'];
                    $feedbackcomment->commentformat = FORMAT_HTML;
                    $DB->update_record('assignfeedback_comments', $feedbackcomment);
                } else {
                    $feedbackcomment = new stdClass();
                    $feedbackcomment->commenttext = $info['comments'];
                    $feedbackcomment->commentformat = FORMAT_HTML;
                    $feedbackcomment->grade = $grade->id;
                    $feedbackcomment->assignment = $this->assignment->get_instance()->id;
                    $DB->insert_record('assignfeedback_comments', $feedbackcomment);
                }
            }
        }

        if ($comments) {
            list($insql, $params) = $DB->get_in_or_equal(array_keys($compsubids));
            $DB->execute("UPDATE {assignsubmission_compsubs} SET commentpublished = 1 WHERE id $insql", $params);
        }

        $transaction->allow_commit();
    }
}
