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

use assignsubmission_comparativejudgement\exclusion;

/**
 * @package    assignsubmission_comparativejudgement
 * @copyright 2020 Andrew Hancox at Open Source Learning <andrewdchancox@googlemail.com>
 * @copyright 2020 Ian Jones at Loughborough University <I.Jones@lboro.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_assignsubmission_comparativejudgement_subplugin extends backup_subplugin {
    private static $parentinforadded = false;

    /**
     * Returns the subplugin information to attach to submission element
     *
     * @return backup_subplugin_element
     */
    protected function define_submission_subplugin_structure() {
        // Create XML elements.
        $subplugin = $this->get_subplugin_element();

        // Unpleasant hack as I can't see a way to have a submission plugin write to the activity node of
        // the document.
        if (!self::$parentinforadded) {
            $activityrootelement = $subplugin->get_parent()->get_parent()->get_parent()->get_parent();

            $parent = new backup_nested_element('assignsubmission_activity_settings');
            $activityrootelement->add_child($parent);

            $asrankingelement = new backup_nested_element(
                'assignsubmission_ranking',
                ['id'],
                ['reliability', 'usermodified', 'timecreated', 'timemodified']
            );
            $asrankingelement->annotate_ids('user', 'usermodified');

            // Connect XML elements into the tree.
            $subs = new backup_nested_element('rankingsubs');
            $asrankingelement->add_child($subs);
            $parent->add_child($asrankingelement);

            // Set source to populate the data.
            $asrankingelement->set_source_table(
                'assignsubmission_ranking',
                ['assignmentid' => backup::VAR_ACTIVITYID]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_rankingsub',
                null,
                ['submissionid', 'score']
            );

            // Connect XML elements into the tree.
            $subs->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_table(
                'assignsubmission_rankingsub',
                ['rankingid' => backup::VAR_PARENTID]
            );

            $ascompelement = new backup_nested_element(
                'assignsubmission_comp',
                ['id'],
                ['winningsubmission', 'winningsubmissionposition', 'timetaken', 'usermodified', 'timecreated',
                'timemodified']
            );
            $ascompelement->annotate_ids('user', 'usermodified');

            // Connect XML elements into the tree.
            $subs = new backup_nested_element('compsubs');
            $ascompelement->add_child($subs);
            $parent->add_child($ascompelement);

            // Set source to populate the data.
            $ascompelement->set_source_table(
                'assignsubmission_comp',
                ['assignmentid' => backup::VAR_ACTIVITYID]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_compsubs',
                ['id'],
                ['submissionid', 'comments', 'commentsformat', 'commentpublished']
            );

            // Connect XML elements into the tree.
            $subs->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_table(
                'assignsubmission_compsubs',
                ['judgementid' => backup::VAR_PARENTID]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_email',
                null,
                ['assignmentid', 'delay', 'subject', 'body', 'usermodified', 'timecreated',
                'timemodified']
            );
            $subpluginelement->annotate_ids('user', 'usermodified');

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_table(
                'assignsubmission_email',
                ['assignmentid' => backup::VAR_ACTIVITYID]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_exclusion_submission',
                null,
                ['type', 'entityid']
            );

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_sql(
                '
            SELECT type, entityid FROM {assignsubmission_exclusion}
            WHERE type = :submissiontype AND assignmentid = :assignmentid',
                [
                            'submissiontype' => ['sqlparam' => exclusion::EXCLUSION_TYPE_SUBMISSION],
                            'assignmentid'   => backup::VAR_ACTIVITYID,
                ]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_exclusion_judge',
                null,
                ['type', 'entityid']
            );

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_sql(
                '
            select type, entityid from {assignsubmission_exclusion} WHERE
            type = :judgetype AND assignmentid = :assignmentid',
                [
                            'judgetype'      => ['sqlparam' => exclusion::EXCLUSION_TYPE_JUDGE],
                            'assignmentid'   => backup::VAR_ACTIVITYID,
                ]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_exclusion_comment',
                null,
                ['type', 'entityid']
            );

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_sql(
                '
            select type, entityid from {assignsubmission_exclusion} WHERE
            type = :comparisoncommenttype AND assignmentid = :assignmentid',
                [
                            'comparisoncommenttype' => ['sqlparam' => exclusion::EXCLUSION_TYPE_COMPARISONCOMMENT],
                            'assignmentid'          => backup::VAR_ACTIVITYID,
                ]
            );

            $subpluginelement = new backup_nested_element(
                'assignsubmission_exemplars',
                null,
                ['submissionid', 'title']
            );

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            $subpluginelement->set_source_sql(
                '
            select ex.* from {assignsubmission_exemplars} ex
                inner join {assign_submission} ass on ass.id = ex.submissionid
                where ass.assignment = :assignmentid
            ',
                [
                            'assignmentid'          => backup::VAR_ACTIVITYID,
                ]
            );

            self::$parentinforadded = true;
        }

        return $subplugin;
    }
}
