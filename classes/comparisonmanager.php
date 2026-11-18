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
use core_availability\info_module;
use stdClass;

class comparisonmanager {
    private $userid;
    private $assignment;
    private $assignmentinstance;
    private $randomise = true;

    /** Random draws to try before falling back to counting every allowed pair. */
    const MAX_RANDOM_DRAWS = 100;

    /**
     * Function comment.
     */
    public function __construct($userid, assign $assignment) {
        $this->userid = $userid;
        $this->assignment = $assignment;
        $this->assignmentinstance = $this->assignment->get_instance($userid);

        if (defined('BEHAT_SITE_RUNNING')) {
            $this->randomise = false;
        }
    }

    public function disablerandomness() {
        if (!(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            throw new \Exception('disbalerandomness only applicable within unit tests');
        }

        $this->randomise = false;
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

    /**
     * Get a pair of submissions for this user to judge.
     *
     * Pairs are drawn uniformly at random from all allowed pairs (with replacement across judges).
     * Drawing a pair needs only a few small, indexed queries, however many submissions or
     * comparisons the assignment has, so it doesn't slow down as a session grows. A pair is only offered if:
     *  - neither submission is the user's own (or their group's),
     *  - both submissions are the latest attempt and fully submitted,
     *  - they are not both exemplars (unless allowcompareexemplars is set),
     *  - this user has not already judged the pair (unless allowrepeatcomparisons is set).
     *
     * The drawn pair is kept in the user's session until they record a judgement, so reloading the
     * page shows the same pair rather than letting the judge pick a different one.
     *
     * @return array|false Two submission records keyed by id, left-hand one first, or false if none.
     */
    public function getpairtojudge() {
        if (!$this->canuserjudge()) {
            return false;
        }

        $candidates = $this->getcandidatesubmissions();
        if (count($candidates) < 2) {
            return false;
        }

        $settings = $this->getsettings();
        $allowrepeats = !empty($settings->allowrepeatcomparisons);
        $allowexemplarpairs = !empty($settings->allowcompareexemplars);
        $judgedpairs = $this->getjudgedpairs();
        $judgementcount = static::countjudgementsmade($this->userid, $this->assignmentinstance->id);

        $isvalid = function ($a, $b) use ($candidates, $allowrepeats, $allowexemplarpairs, $judgedpairs): bool {
            if ($a == $b || !isset($candidates[$a]) || !isset($candidates[$b])) {
                return false;
            }
            if (!$allowexemplarpairs && !empty($candidates[$a]->exemp) && !empty($candidates[$b]->exemp)) {
                return false;
            }
            if (!$allowrepeats && isset($judgedpairs[self::pairkey($a, $b)])) {
                return false;
            }
            return true;
        };

        $pair = $this->getstoredpair($judgementcount);
        if (!$pair || !$isvalid($pair[0], $pair[1])) {
            if ($this->randomise) {
                $pair = $this->drawrandompair(array_keys($candidates), $isvalid);
            } else {
                $pair = $this->drawdeterministicpair(array_keys($candidates), $isvalid, $judgedpairs);
            }
        }

        if (!$pair) {
            $this->clearstoredpair();
            return false;
        }

        $this->storepair($pair, $judgementcount);

        [$left, $right] = $pair;
        return [$left => $candidates[$left], $right => $candidates[$right]];
    }

    /**
     * Submissions this user may be shown, keyed by submission id, ordered by userid then id.
     *
     * @return stdClass[]
     */
    private function getcandidatesubmissions(): array {
        global $DB;

        $submission = $this->getsubmission();

        if ($this->assignmentinstance->teamsubmission) {
            $teamfrag = ' sub.userid <= 0 '; // Don't return individual submissions that make up the group.
        } else {
            $teamfrag = ' sub.userid <> 0 '; // Don't return rogue group submissions.
        }

        $sql = "SELECT sub.*, exemp.id AS exemp
                  FROM {assign_submission} sub
             LEFT JOIN {assignsubmission_exemplars} exemp ON exemp.submissionid = sub.id
                 WHERE sub.assignment = :assignment
                   AND sub.status = :status
                   AND sub.latest = 1
                   AND sub.id <> :excludeid
                   AND $teamfrag
              ORDER BY sub.userid, sub.id";

        return $DB->get_records_sql($sql, [
            'assignment' => $this->assignmentinstance->id,
            'status'     => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
            'excludeid'  => !empty($submission) ? $submission->id : -1,
        ]);
    }

    /**
     * Pairs this user has already judged on this assignment.
     *
     * @return int[] Number of times judged, keyed by pairkey().
     */
    private function getjudgedpairs(): array {
        global $DB;

        $sql = "SELECT compsub.id, compsub.judgementid, compsub.submissionid
                  FROM {assignsubmission_comp} comp
                  JOIN {assignsubmission_compsubs} compsub ON compsub.judgementid = comp.id
                 WHERE comp.assignmentid = :assignmentid
                   AND comp.usermodified = :userid";

        $byjudgement = [];
        $rs = $DB->get_recordset_sql($sql, ['assignmentid' => $this->assignmentinstance->id, 'userid' => $this->userid]);
        foreach ($rs as $row) {
            $byjudgement[$row->judgementid][] = (int) $row->submissionid;
        }
        $rs->close();

        $pairs = [];
        foreach ($byjudgement as $submissionids) {
            if (count($submissionids) != 2) {
                continue;
            }
            $key = self::pairkey($submissionids[0], $submissionids[1]);
            $pairs[$key] = ($pairs[$key] ?? 0) + 1;
        }

        return $pairs;
    }

    /**
     * Order-independent key for a pair of submission ids.
     */
    private static function pairkey($a, $b): string {
        return min((int) $a, (int) $b) . '|' . max((int) $a, (int) $b);
    }

    /**
     * Draw a pair uniformly at random: the left-hand submission from all candidates, the right-hand
     * one from all candidates except the left-hand one, redrawing if the pair isn't allowed.
     *
     * If repeated draws keep failing (only likely when the pool is small or nearly exhausted for this
     * judge) count the allowed pairs and pick one of those uniformly instead.
     *
     * @param int[] $ids Candidate submission ids.
     * @param callable $isvalid
     * @return int[]|false [leftid, rightid]
     */
    private function drawrandompair(array $ids, callable $isvalid) {
        $n = count($ids);

        for ($draw = 0; $draw < self::MAX_RANDOM_DRAWS; $draw++) {
            $l = random_int(0, $n - 1);
            $r = random_int(0, $n - 2);
            if ($r >= $l) {
                $r++;
            }
            if ($isvalid($ids[$l], $ids[$r])) {
                return [$ids[$l], $ids[$r]];
            }
        }

        // Fallback: count the allowed pairs, then walk to a randomly chosen one (avoids holding them all in memory).
        $validcount = 0;
        for ($a = 0; $a < $n; $a++) {
            for ($b = $a + 1; $b < $n; $b++) {
                if ($isvalid($ids[$a], $ids[$b])) {
                    $validcount++;
                }
            }
        }

        if ($validcount == 0) {
            return false;
        }

        $target = random_int(0, $validcount - 1);
        for ($a = 0; $a < $n; $a++) {
            for ($b = $a + 1; $b < $n; $b++) {
                if ($isvalid($ids[$a], $ids[$b]) && $target-- == 0) {
                    return random_int(0, 1) ? [$ids[$a], $ids[$b]] : [$ids[$b], $ids[$a]];
                }
            }
        }

        return false;
    }

    /**
     * Deterministic pair choice for Behat and unit tests only: the first allowed pair (in userid order)
     * that this user has judged the fewest times, with the higher userid on the left.
     *
     * @param int[] $ids Candidate submission ids, ordered by userid.
     * @param callable $isvalid
     * @param int[] $judgedpairs
     * @return int[]|false [leftid, rightid]
     */
    private function drawdeterministicpair(array $ids, callable $isvalid, array $judgedpairs) {
        $best = false;
        $bestcount = PHP_INT_MAX;
        $n = count($ids);

        for ($a = 0; $a < $n; $a++) {
            for ($b = $a + 1; $b < $n; $b++) {
                if (!$isvalid($ids[$a], $ids[$b])) {
                    continue;
                }
                $count = $judgedpairs[self::pairkey($ids[$a], $ids[$b])] ?? 0;
                if ($count < $bestcount) {
                    $best = [$ids[$b], $ids[$a]];
                    $bestcount = $count;
                }
            }
        }

        return $best;
    }

    /**
     * The session only holds a pair for the logged-in user (not, e.g., for judges a scheduled task checks).
     */
    private function canstorepair(): bool {
        global $USER;
        return !empty($USER->id) && $USER->id == $this->userid;
    }

    /**
     * The pair this user was last shown, if they haven't recorded a judgement since.
     *
     * @return int[]|false [leftid, rightid]
     */
    private function getstoredpair(int $judgementcount) {
        global $SESSION;

        if (!$this->canstorepair()) {
            return false;
        }

        $stored = $SESSION->assignsubmission_comparativejudgement_pairs[$this->assignmentinstance->id] ?? null;
        if (
            empty($stored) || $stored['userid'] != $this->userid ||
            $stored['judgementcount'] != $judgementcount
        ) {
            return false;
        }

        return [$stored['left'], $stored['right']];
    }

    private function storepair(array $pair, int $judgementcount) {
        global $SESSION;

        if (!$this->canstorepair()) {
            return;
        }

        if (!isset($SESSION->assignsubmission_comparativejudgement_pairs)) {
            $SESSION->assignsubmission_comparativejudgement_pairs = [];
        }
        $SESSION->assignsubmission_comparativejudgement_pairs[$this->assignmentinstance->id] = [
            'userid'         => $this->userid,
            'left'           => $pair[0],
            'right'          => $pair[1],
            'judgementcount' => $judgementcount,
        ];
    }

    private function clearstoredpair() {
        global $SESSION;

        if ($this->canstorepair()) {
            unset($SESSION->assignsubmission_comparativejudgement_pairs[$this->assignmentinstance->id]);
        }
    }

    public function getalljudges() {
        global $CFG, $DB;

        $users = [];
        $settings = $this->getsettings();

        if (empty($settings->judges)) {
            return $users;
        }

        $judges = $settings->judges;

        if (in_array(assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS, $judges)) {
            $judges = array_merge($judges, explode(',', $CFG->gradebookroles));
        }

        if (in_array(assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED, $judges)) {
            // It doesn't look like it but this does cover group submissions as well.
            // Each member of a group submission should have a personal submission record with no content as well.
            $users = array_merge($users, array_keys($DB->get_records_sql(
                'SELECT userid FROM {assign_submission} WHERE assignment = :assignment AND
                    status = :status AND groupid = 0 AND latest = 1 GROUP BY userid',
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

        $info = new info_module($this->assignment->get_course_module());
        return array_keys($info->filter_user_list(array_combine($users, $users)));
    }

    public function redirectusertojudge() {
        if (!$this->canuserjudge()) {
            return false;
        }

        $settings = $this->getsettings();
        // Are they over the minimum number of judgements or is minjudgements empty.
        $comparisoncount = static::countjudgementsmade($this->userid, $this->assignmentinstance->id);
        if (empty($settings->minjudgementsperuser) || $comparisoncount >= $settings->minjudgementsperuser) {
            return false;
        }

        // If the user is gradable but hasn't yet submitted a response then definitely don't redirect.
        if ($this->isusergradable() && $this->getsubmission()->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            return false;
        }

        return !empty($this->getpairtojudge());
    }

    public static function countjudgementsmade($userid, $assignmentid) {
        return comparison::count_records(['usermodified' => $userid, 'assignmentid' => $assignmentid]);
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
        $settings = $this->getsettings();
        $plugin = assign_submission_comparativejudgement::getplugin($this->assignment);
        if (!$plugin->is_enabled() || empty($settings->judges)) {
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

        if (
            $this->isusergradable() && empty($settings->judgementswhileeditable) &&
            $this->assignment->submissions_open($this->userid)
        ) {
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
    }
}
