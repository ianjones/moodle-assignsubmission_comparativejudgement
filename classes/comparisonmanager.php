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
use assign_submission_comparativejudgement;

class comparisonmanager {
    private $userid;
    private $assignment;
    private $assignmentinstance;

    public function __construct($userid, assign $assignment) {
        $this->userid = $userid;
        $this->assignment = $assignment;
        $this->assignmentinstance = $this->assignment->get_instance($userid);
    }

    private function getsubmission() {
        if ($this->assignmentinstance->teamsubmission) {
            return $this->assignment->get_group_submission($this->userid, 0, false);
        } else {
            return $this->assignment->get_user_submission($this->userid, false);
        }
    }

    private function getsettings() {
        return assign_submission_comparativejudgement::getpluginsettings($this->assignment);
    }

    // User has not judged this combination ever.
    // Not both exemplars.
    // Not their own submission.
    // Submission is fully submitted.
    // User has not judged either assignment.
    // Number of judgements on this assignment.
    // Variable $urgent means below min or never judged.
    public function getpairtojudge(bool $urgent = false) {
        global $DB;

        if (!$this->canuserjudge()) {
            return false;
        }

        $submission = $this->getsubmission();

        if (!empty($submission)) {
            $submissiontoexclude = $submission->id;
        } else {
            $submissiontoexclude = -1;
        }

        $fullysubmitted = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assignmentid = $this->assignmentinstance->id;

        if ($this->assignmentinstance->teamsubmission) {
            $teamfrag = ' sub.userid <= 0 '; // Don't return individual submissions that make up the group.
        } else {
            $teamfrag = ' sub.userid <> 0 '; // Don't return rogue group submissions.
        }

        $settings = $this->getsettings();

        // Get all submissions that have not been judged against every other submission by this user.
        for ($i = 0; $i < 2; $i++) {
            if ($urgent) {
                if (!empty($settings->minjudgementspersubmission)) {
                    $threshold = "HAVING SUM(CASE WHEN comp.usermodified = $this->userid THEN 1 ELSE 0 END) < $settings->minjudgementspersubmission";
                } else {
                    $threshold = "HAVING SUM(CASE WHEN comp.usermodified = $this->userid THEN 1 ELSE 0 END) < 1";
                }
            } else {
                $threshold = '';
            }

            $sql[$i] = "SELECT  sub.id as id_$i, sub.assignment as assignment_$i, sub.userid as userid_$i, sub.timecreated as timecreated_$i, sub.timemodified as timemodified_$i,
                        sub.status as status_$i, sub.groupid as groupid_$i, sub.attemptnumber as attemptnumber_$i, sub.latest as latest_$i,
                        count(comp.id) AS totaljudgements_$i, SUM(CASE WHEN comp.usermodified = $this->userid THEN 1 ELSE 0 END) AS totaluserjudgements_$i, exemp.id as exemp_$i
                FROM {assign_submission} sub
                         LEFT JOIN {assignsubmission_compsubs} compsub ON compsub.submissionid = sub.id
                         LEFT JOIN {assignsubmission_comp} comp ON compsub.judgementid = comp.id
                         LEFT JOIN {assignsubmission_exemplars} exemp ON exemp.submissionid = sub.id
                WHERE sub.id <> $submissiontoexclude
                  AND sub.status = '$fullysubmitted'
                  AND sub.latest = 1
                  AND sub.assignment = $assignmentid
                  AND $teamfrag
                GROUP BY sub.id, sub.assignment, sub.userid, sub.timecreated, sub.timemodified, sub.status, sub.groupid, sub.attemptnumber, sub.latest, exemp.id
                  $threshold";
        }

        $sql = "
            SELECT subone.*, subzero.*
            FROM ($sql[0]) as subzero
            INNER JOIN ($sql[1]) as subone on subzero.id_0 <> subone.id_1
            LEFT JOIN (
                SELECT comp.winningsubmission as winning, compsub.submissionid as loosing
                FROM {assignsubmission_comp} comp
                INNER JOIN {assignsubmission_compsubs} compsub ON compsub.judgementid = comp.id and compsub.submissionid <> comp.winningsubmission
                WHERE comp.usermodified = $this->userid
                )  as subs ON (subzero.id_0 = subs.winning and subone.id_1 = subs.loosing) OR (subone.id_1 = subs.winning and subzero.id_0 = subs.loosing
            )
             WHERE subs.loosing IS NULL AND (subone.exemp_1 IS NULL OR subzero.exemp_0 IS NULL)
             ";

        $submissions = $DB->get_records_sql($sql, null, 0, 1);

        if (count($submissions) < 1) {
            return false;
        }

        $submissionpair = reset($submissions);
        $subone = new \stdClass();
        $subtwo = new \stdClass();

        foreach ((array) $submissionpair as $key => $value) {
            list($key, $index) = explode('_', $key);
            if ($index == 0) {
                $subone->$key = $value;
            } else if ($index == 1) {
                $subtwo->$key = $value;
            }
        }

        return [$subone->id => $subone, $subtwo->id => $subtwo];
    }

    public function getalljudges() {
        global $CFG, $DB;

        $users = [];
        $settings = $this->getsettings();

        $judges = $settings->judges;

        if (in_array(assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS, $judges)) {
            $judges = array_merge($judges, explode(',', $CFG->gradebookroles));
        }

        if (in_array(assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED, $judges)) {
            // It doesn't look like it but this does cover group submissions as wel.
            // Each member of a group submission should have a personal submission record with no content as well.
            $users = array_merge($users, array_keys($DB->get_records_sql(
                    'SELECT userid FROM {assign_submission} WHERE assignment = :assignment AND status = :status AND groupid = 0 AND latest = 1 GROUP BY userid',
                    ['status' => ASSIGN_SUBMISSION_STATUS_SUBMITTED, 'assignment' => $this->assignmentinstance->id]
            )));
        }

        foreach ($judges as $roleid) {
            if ($roleid < 0) {
                continue;
            }
            if (empty($roleid)) {
                continue;
            }
            $users = array_merge($users, array_keys(get_role_users($roleid, $this->assignment->get_context(), true)));
        }

        return $users;
    }

    public function redirectusertojudge() {
        if (!$this->canuserjudge()) {
            return false;
        }

        $settings = $this->getsettings();
        // Are they over the minimum number of judgements or is minjudgements empty.
        $comparisoncount =
                comparison::count_records(['usermodified' => $this->userid, 'assignmentid' => $this->assignmentinstance->id]);
        if (empty($settings->minjudgementsperuser) || $comparisoncount >= $settings->minjudgementsperuser) {
            return false;
        }

        if ($this->alldatespastorempty()) {
            return !empty($this->getpairtojudge());
        } else {
            return !empty($this->getpairtojudge(true));
        }
    }

    private function isusergradable() {
        global $CFG;

        $userroles = get_user_roles($this->assignment->get_context(), $this->userid);
        $gradebookroles = explode(',', $CFG->gradebookroles);
        foreach ($userroles as $role) {
            if (in_array($role->roleid, $gradebookroles)) {
                return true;
            }
        }

        return false;
    }

    public function isuserajudge() {
        global $CFG;

        $settings = $this->getsettings();
        $plugin = assign_submission_comparativejudgement::getplugin($this->assignment);
        if (!$plugin->is_enabled()) {
            return false;
        }

        $userroles = get_user_roles($this->assignment->get_context(), $this->userid);

        // Deal with actual roles.
        foreach ($userroles as $role) {
            if (in_array($role->roleid, $settings->judges)) {
                return true;
            }
        }

        if (in_array(assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS, $settings->judges)) {
            if ($this->isusergradable()) {
                return true;
            }
        }

        $submission = $this->getsubmission();
        if (in_array(assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED, $settings->judges)) {
            if (!empty($submission) && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                return true;
            }
        }

        return false;
    }

    /*
        Can the user judge (CAN):
        Is judgementstartdate set and past
        Is max judgements unset or is user below max judgements
        Is user unable to edit submissions
        Is the user in the pool of judges?
    */
    public function canuserjudge() {
        $plugin = assign_submission_comparativejudgement::getplugin($this->assignment);
        if (!$plugin->is_enabled()) {
            return false;
        }

        $now = time();

        $settings = $this->getsettings();
        // Be after start of judgements.
        if (!empty($settings->judgementstartdate) && $now < $settings->judgementstartdate) {
            return false;
        }

        // Not have exceeded max judgements per user.
        $comparisoncount =
                comparison::count_records(['usermodified' => $this->userid, 'assignmentid' => $this->assignmentinstance->id]);
        if (!empty($settings->maxjudgementsperuser) && $comparisoncount >= $settings->maxjudgementsperuser) {
            return false;
        }

        if ($this->isusergradable() && empty($settings->judgementswhileeditable) &&
                $this->assignment->submissions_open($this->userid)) {
            return false;
        }

        if (!$this->isuserajudge()) {
            return false;
        }

        return true;
    }

    public function alldatespastorempty() {
        $now = time();

        $settings = $this->getsettings();

        // Be after start of judgements.
        if (!empty($settings->judgementstartdate)) {
            return $now > $settings->judgementstartdate;
        }

        $duedate = $this->assignmentinstance->duedate;
        $cutoffdate = $this->assignmentinstance->cutoffdate;

        if (!empty($cutoffdate)) {
            $lastdate = $cutoffdate;
        } else {
            $lastdate = $duedate;
        }

        if (!empty($lastdate)) {
            return $now > $lastdate;
        }

        return true;
    }

    public function getlastdate() {
        $settings = $this->getsettings();

        // Be after start of judgements.
        if (!empty($settings->judgementstartdate)) {
            return $settings->judgementstartdate;
        }

        $duedate = $this->assignmentinstance->duedate;
        $cutoffdate = $this->assignmentinstance->cutoffdate;

        if (!empty($cutoffdate)) {
            return $cutoffdate;
        } else {
            return $duedate;
        }

        return false;
    }
}