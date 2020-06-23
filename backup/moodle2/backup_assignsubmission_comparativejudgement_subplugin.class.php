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
 * This file contains the class for backup of this submission plugin
 *
 * @package assignsubmission_comparativejudgement
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_comparativejudgement\exclusion;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup onlinetext submissions
 *
 * This just adds its filearea to the annotations and records the submissiontext and format
 *
 * @package assignsubmission_comparativejudgement
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

            //assignsubmission_ranking
            $assignsubmission_ranking_element = new backup_nested_element('assignsubmission_ranking',
                    ['id'],
                    array('reliability', 'usermodified', 'timecreated', 'timemodified'));
            $assignsubmission_ranking_element->annotate_ids('user', 'usermodified');

            // Connect XML elements into the tree.
            $subs = new backup_nested_element('rankingsubs');
            $assignsubmission_ranking_element->add_child($subs);
            $parent->add_child($assignsubmission_ranking_element);

            // Set source to populate the data.f
            $assignsubmission_ranking_element->set_source_table('assignsubmission_ranking',
                    array('assignmentid' => backup::VAR_ACTIVITYID));

            //assignsubmission_rankingsub
            $subpluginelement = new backup_nested_element('assignsubmission_rankingsub',
                    null,
                    array('submissionid', 'score'));

            // Connect XML elements into the tree.
            $subs->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_table('assignsubmission_rankingsub',
                    array('rankingid' => backup::VAR_PARENTID));

            //assignsubmission_comp
            $assignsubmission_comp_element = new backup_nested_element('assignsubmission_comp',
                    ['id'],
                    array('winningsubmission', 'winningsubmissionposition', 'timetaken', 'usermodified', 'timecreated',
                            'timemodified'));
            $assignsubmission_comp_element->annotate_ids('user', 'usermodified');

            // Connect XML elements into the tree.
            $subs = new backup_nested_element('compsubs');
            $assignsubmission_comp_element->add_child($subs);
            $parent->add_child($assignsubmission_comp_element);

            // Set source to populate the data.
            $assignsubmission_comp_element->set_source_table('assignsubmission_comp',
                    array('assignmentid' => backup::VAR_ACTIVITYID));

            //assignsubmission_compsubs
            $subpluginelement = new backup_nested_element('assignsubmission_compsubs',
                    ['id'],
                    array('submissionid', 'comments', 'commentsformat', 'commentpublished'));

            // Connect XML elements into the tree.
            $subs->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_table('assignsubmission_compsubs',
                    array('judgementid' => backup::VAR_PARENTID));

            //assignsubmission_email
            $subpluginelement = new backup_nested_element('assignsubmission_email',
                    null,
                    array('assignmentid', 'delay', 'subject', 'body', 'usermodified', 'timecreated',
                            'timemodified'));
            $subpluginelement->annotate_ids('user', 'usermodified');

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_table('assignsubmission_email',
                    array('assignmentid' => backup::VAR_ACTIVITYID));

            //assignsubmission_exclusion ACTIVITY LEVEL
            $subpluginelement = new backup_nested_element('assignsubmission_exclusion_submission',
                    null,
                    array('type', 'entityid'));

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_sql('
            SELECT type, entityid FROM {assignsubmission_exclusion} 
            WHERE type = :submissiontype AND entityid IN (
                SELECT id FROM {assign_submission} WHERE assignment = :assignmentid
            )
            ',
                    [
                            'submissiontype' => ['sqlparam' => exclusion::EXCLUSION_TYPE_SUBMISSION],
                            'assignmentid'   => backup::VAR_ACTIVITYID
                    ]);

            $subpluginelement = new backup_nested_element('assignsubmission_exclusion_judge',
                    null,
                    array('type', 'entityid'));

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_sql('
            select type, entityid from {assignsubmission_exclusion} WHERE
            type = :judgetype
            ',
                    [
                            'judgetype'      => ['sqlparam' => exclusion::EXCLUSION_TYPE_JUDGE]
                    ]);

            //assignsubmission_exclusion ACTIVITY LEVEL
            $subpluginelement = new backup_nested_element('assignsubmission_exclusion_comment',
                    null,
                    array('type', 'entityid'));

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            // Set source to populate the data.
            $subpluginelement->set_source_sql('
            select type, entityid from {assignsubmission_exclusion} WHERE
            type = :comparisoncommenttype AND entityid in (
                SELECT compsub.id 
                FROM {assignsubmission_compsubs} compsub
                INNER JOIN {assign_submission} ass on ass.id = compsub.submissionid
                WHERE ass.assignment = :assignmentid
                )
            ',
                    [
                            'comparisoncommenttype' => ['sqlparam' => exclusion::EXCLUSION_TYPE_COMPARISONCOMMENT],
                            'assignmentid'          => backup::VAR_ACTIVITYID
                    ]);

            //assignsubmission_exemplars
            $subpluginelement = new backup_nested_element('assignsubmission_exemplars',
                    null,
                    array('submissionid', 'title'));

            // Connect XML elements into the tree.
            $parent->add_child($subpluginelement);

            $subpluginelement->set_source_sql('
            select ex.* from {assignsubmission_exemplars} ex
                inner join {assign_submission} ass on ass.id = ex.submissionid
                where ass.assignment = :assignmentid
            ',
                    [
                            'assignmentid'          => backup::VAR_ACTIVITYID
                    ]);

            self::$parentinforadded = true;
        }

        return $subplugin;
    }

}
