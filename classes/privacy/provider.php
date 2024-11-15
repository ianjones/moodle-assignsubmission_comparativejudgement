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

namespace assignsubmission_comparativejudgement\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadataprovider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_user_data_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;

class provider implements metadataprovider,
        \mod_assign\privacy\assignsubmission_provider,
        \mod_assign\privacy\assignsubmission_user_provider,
        \core_privacy\local\request\core_userlist_provider,
        core_user_data_provider {

    /**
     * Return meta data about this plugin.
     *
     * @param collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {

        $detail = [
                'usermodified'              => 'privacy:metadata:assignsubmission_comparativejudgement:usermodified',
                'winningsubmission'         => 'privacy:metadata:assignsubmission_comparativejudgement:winningsubmission',
                'winningsubmissionposition' => 'privacy:metadata:assignsubmission_comparativejudgement:winningsubmissionposition',
                'timetaken'                 => 'privacy:metadata:assignsubmission_comparativejudgement:timetaken',
        ];
        $collection->add_database_table('assignsubmission_comp', $detail,
                'privacy:metadata:assignsubmission_comparativejudgement:assignsubmission_comp:tablepurpose');

        $detail = [
                'judgementid'  => 'privacy:metadata:assignsubmission_comparativejudgement:judgementid',
                'submissionid' => 'privacy:metadata:assignsubmission_comparativejudgement:submissionid',
                'comments'     => 'privacy:metadata:assignsubmission_comparativejudgement:comments',
        ];
        $collection->add_database_table('assignsubmission_compsubs', $detail,
                'privacy:metadata:assignsubmission_comparativejudgement:assignsubmission_compsubs:tablepurpose');

        $detail = [
                'rankingid'    => 'privacy:metadata:assignsubmission_comparativejudgement:rankingid',
                'submissionid' => 'privacy:metadata:assignsubmission_comparativejudgement:submissionid',
                'score'        => 'privacy:metadata:assignsubmission_comparativejudgement:score',
        ];
        $collection->add_database_table('assignsubmission_rankingsub', $detail,
                'privacy:metadata:assignsubmission_comparativejudgement:assignsubmission_rankingsub:tablepurpose');

        return $collection;
    }

    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param int $userid The user ID that we are finding contexts for.
     * @param contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
        // This is already fetched from mod_assign.
    }

    /**
     * This is also covered by the mod_assign provider and it's queries.
     * We worry about judgements submitted under the core_user_data_provider interface related methods
     *
     * @param \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the assign_submission table then please fill in this method.
     *
     * @param \core_privacy\local\request\userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        $context = $userlist->get_context();
        $userlist->add_from_sql(
                'usermodified',
                'SELECT usermodified FROM {assignsubmission_comp} WHERE assignmentid = :assignmentid',
                ['assignmentid' => $context->instanceid]
        );
    }

    /**
     * Export all user data for this plugin.
     *
     * @param assign_plugin_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        self::judgementsreceived($exportdata);
        self::rankings($exportdata);
    }

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     *
     * @param assign_plugin_request_data $requestdata Data useful for deleting user data from this sub-plugin.
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        self::deleteallonassignid($requestdata->get_assignid());
    }

    /**
     * A call to this method should delete user data (where practicle) from the userid and context.
     *
     * @param assign_plugin_request_data $deletedata Details about the user and context to focus the deletion.
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;

        // Delete comparisons received.
        $DB->delete_records('assignsubmission_compsubs', ['submissionid' => $deletedata->get_pluginobject()->id]);
        $DB->delete_records('assignsubmission_comp', ['winningsubmission' => $deletedata->get_pluginobject()->id]);

        // Delete rankings.
        $DB->delete_records('assignsubmission_rankingsub', ['submissionid' => $deletedata->get_pluginobject()->id]);
    }

    /**
     * Deletes all submissions for the submission ids / userids provided in a context.
     * assign_plugin_request_data contains:
     * - context
     * - assign object
     * - submission ids (pluginids)
     * - user ids
     *
     * @param assign_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        global $DB;

        if (empty($deletedata->get_submissionids())) {
            return;
        }

        // Delete comparisons received.
        $DB->delete_records_list('assignsubmission_compsubs', 'submissionid', $deletedata->get_submissionids());
        $DB->delete_records_list('assignsubmission_comp', 'winningsubmission', $deletedata->get_submissionids());

        // Delete rankings.
        $DB->delete_records_list('assignsubmission_rankingsub', 'submissionid', $deletedata->get_submissionids());
    }

    /**
     * Methods below implement core_user_data_provider interface to pick up the data the
     * assignsubmission_provider related methods can't reach - specifically comparisons made by (not on) the user.
     */

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql("
        SELECT ctx.id
        FROM {assignsubmission_comp} comp
        INNER JOIN {course_modules} cm on comp.assignmentid = cm.instance
        INNER JOIN {modules} m on m.name = 'assign' and m.id = cm.module
        INNER JOIN {context} ctx on ctx.instanceid = cm.id AND ctx.contextlevel = :cmcontextlevel
        WHERE comp.usermodified = :userid
        ", ['userid' => $userid, 'cmcontextlevel' => CONTEXT_MODULE]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $contextids = $contextlist->get_contextids();

        foreach ($contextids as $contextid) {
            $context = \context::instance_by_id($contextid);

            $path = [
                    get_string('privacy:judgementmade', 'assignsubmission_comparativejudgement'),
            ];

            $judgements = [];
            foreach (self::judgementsmade($userid, $contextid) as $judgement) {
                $judgements[] = (object)
                [
                        'submissionid' => $judgement->submissionid,
                        'winner'       => $judgement->winningsubmission == $judgement->submissionid,
                        'time'         => userdate($judgement->timemodified),
                        'comments'     => format_text($judgement->comments, $judgement->commentsformat),
                ];
            }

            $data[] = (object) [
                    'judgements' => $judgements,
            ];
            writer::with_context($context)->export_related_data(
                    $path,
                    $DB->get_field('assign', 'name', ['id' => $context->instanceid]),
                    $data
            );
        }
    }

    /**
     * @param \context|context $context
     * @return mixed
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        self::deleteallonassignid($context->instanceid->id);
    }

    /**
     * Only worry about judgements made, judgements received are picked up elsewhere.
     *
     * @param approved_contextlist $contextlist
     * @return mixed
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $contextids = $contextlist->get_contextids();

        foreach ($contextids as $contextid) {
            $compsubids = array_keys(self::judgementsmade($userid, $contextid));
            $DB->delete_records_list('assignsubmission_compsubs', 'id', $compsubids);

            $context = \context::instance_by_id($contextid);
            $DB->delete_records('assignsubmission_comp', ['assignmentid' => $context->instanceid, 'usermodified' => $userid]);
        }
    }

    /**
     * @param userlist $userlist
     * @return mixed
     */
    public static function get_users_in_context(userlist $userlist) {
        self::get_userids_from_context($userlist);
    }

    /**
     * Only worry about judgements made, judgements received are picked up elsewhere.
     *
     * @param approved_userlist $userlist
     * @return mixed
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            $compsubids = array_keys(self::judgementsmade($userid, $context->id));
            $DB->delete_records_list('assignsubmission_compsubs', 'id', $compsubids);

            $DB->delete_records('assignsubmission_comp', ['assignmentid' => $context->instanceid, 'usermodified' => $userid]);
        }
    }

    /**
     * @param assign_plugin_request_data $exportdata
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function judgementsreceived(assign_plugin_request_data $exportdata): array {
        global $DB;

        $submission = $exportdata->get_pluginobject();

        $judgements = $DB->get_records_sql("select timemodified, comments, commentsformat, winningsubmission
                                from {assignsubmission_comp} comp
                                         inner join {assignsubmission_compsubs} compsubs on
                                    comp.winningsubmission <> compsubs.submissionid and comp.id = compsubs.judgementid
                                where comp.winningsubmission = :submissionid or compsubs.submissionid = :submissionid2
                                group by timemodified, comments, commentsformat, winningsubmission",
                ['submissionid' => $submission->id, 'submissionid2' => $submission->id]);

        $currentpath = array_merge(
                $exportdata->get_subcontext(),
                [get_string('privacy:judgement', 'assignsubmission_comparativejudgement')]
        );
        $items = [];
        foreach ($judgements as $judgement) {
            $items[] = (object)
            [
                    'winner'   => $judgement->winningsubmission == $submission->id,
                    'time'     => userdate($judgement->timemodified),
                    'comments' => format_text($judgement->comments, $judgement->commentsformat,
                            ['context' => $exportdata->get_context()]),
            ];
        }
        writer::with_context($exportdata->get_context())->export_data($currentpath, (object) ['judgements' => $items]);
        return $currentpath;
    }

    /**
     * @param assign_plugin_request_data $exportdata
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function rankings(assign_plugin_request_data $exportdata): void {
        global $DB;

        $submission = $exportdata->get_pluginobject();

        $currentpath = array_merge(
                $exportdata->get_subcontext(),
                [get_string('privacy:ranking', 'assignsubmission_comparativejudgement')]
        );

        $ranking = $DB->get_record('assignsubmission_rankingsub', ['submissionid' => $submission->id]);
        if ($ranking) {
            $data = (object)
            [
                    'score' => $ranking->score,
            ];
            writer::with_context($exportdata->get_context())->export_data($currentpath, $data);
        }
    }

    /**
     * @param int $userid
     * @param int $contextid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function judgementsmade($userid, $contextid): array {
        global $DB;

        return $DB->get_records_sql("SELECT compsubs.id, timemodified, comments, commentsformat, winningsubmission,
                    compsubs.submissionid as submissionid
                    FROM {assignsubmission_comp} comp
                    INNER JOIN {assignsubmission_compsubs} compsubs ON comp.id = compsubs.judgementid
                    INNER JOIN {course_modules} cm ON comp.assignmentid = cm.instance
                    INNER JOIN {modules} m ON m.name = 'assign' AND m.id = cm.module
                    INNER JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :cmcontextlevel
                    WHERE comp.usermodified = :userid AND ctx.id = :contextid
                    GROUP BY compsubs.id, timemodified, comments, commentsformat, winningsubmission, compsubs.submissionid",
                ['userid' => $userid, 'cmcontextlevel' => CONTEXT_MODULE, 'contextid' => $contextid]);
    }

    private static function deleteallonassignid($assignid) {
        global $DB;

        $DB->delete_records_list('assignsubmission_compsubs', 'id', array_keys($DB->get_records_sql('
            SELECT subs.id
            FROM {assignsubmission_comp} comp
            INNER JOIN {assignsubmission_compsubs} subs on subs.judgementid = comp.id
            WHERE comp.assignmentid = :assignmentid',
                ['assignmentid' => $assignid])));
        $DB->delete_records('assignsubmission_comp', ['assignmentid' => $assignid]);

        $DB->delete_records_list('assignsubmission_rankingsub', 'id', array_keys($DB->get_records_sql('
            SELECT subs.id
            FROM {assignsubmission_ranking} ranking
            INNER JOIN {assignsubmission_rankingsub} subs on subs.rankingid = ranking.id
            WHERE ranking.assignmentid = :assignmentid',
                ['assignmentid' => $assignid])));
        $DB->delete_records('assignsubmission_ranking', ['assignmentid' => $assignid]);
    }
}
