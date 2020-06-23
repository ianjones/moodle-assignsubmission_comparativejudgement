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
 * This file contains the class for restore of this submission plugin
 *
 * @package assignsubmission_comparativejudgement
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore subplugin class.
 *
 * Provides the necessary information needed to restore
 * one assign_submission subplugin.
 *
 * @package assignsubmission_comparativejudgement
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_assignsubmission_comparativejudgement_subplugin extends restore_subplugin {

    /**
     * Returns array the paths to be handled by the subplugin at assignment level
     *
     * @return array
     */
    protected function define_submission_subplugin_structure() {

        $paths = array();

        $root = '/activity/assign/assignsubmission_activity_settings/';

        $obj = new restore_path_element('assignsubmission_ranking', $root . 'assignsubmission_ranking');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_rankingsub',
                $root . 'assignsubmission_ranking/rankingsubs/assignsubmission_rankingsub');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_comp', $root . 'assignsubmission_comp');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_compsubs',
                $root . 'assignsubmission_comp/compsubs/assignsubmission_compsubs');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_email', $root . 'assignsubmission_email');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_exclusion_submission', $root . 'assignsubmission_exclusion_submission');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_exclusion_judge', $root . 'assignsubmission_exclusion_judge');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_exclusion_comment', $root . 'assignsubmission_exclusion_comment');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        $obj = new restore_path_element('assignsubmission_exemplars', $root . 'assignsubmission_exemplars');
        $obj->set_processing_object($this);
        $paths[] = $obj;

        return $paths;
    }

    public function process_assignsubmission_comp($data) {
        global $DB;

        $data = (object) $data;
        $data->assignmentid = $this->get_new_parentid('assign');
        $data->winningsubmission = $this->get_mappingid('submission', $data->winningsubmission);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        $compid = $DB->insert_record('assignsubmission_comp', $data);

        $this->set_mapping('assignsubmission_comp', $data->id, $compid);
    }

    public function process_assignsubmission_compsubs($data) {
        global $DB;

        $data = (object) $data;
        $data->submissionid = $this->get_mappingid('submission', $data->submissionid);
        $data->judgementid = $this->get_new_parentid('assignsubmission_comp');

        $compid = $DB->insert_record('assignsubmission_compsubs', $data);

        $this->set_mapping('assignsubmission_compsubs', $data->id, $compid);
    }

    public function process_assignsubmission_ranking($data) {
        global $DB;

        $data = (object) $data;
        $data->assignmentid = $this->get_new_parentid('assign');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        $compid = $DB->insert_record('assignsubmission_ranking', $data);

        $this->set_mapping('assignsubmission_ranking', $data->id, $compid);
    }

    public function process_assignsubmission_rankingsub($data) {
        global $DB;

        $data = (object) $data;
        $data->submissionid = $this->get_mappingid('submission', $data->submissionid);
        $data->rankingid = $this->get_new_parentid('assignsubmission_ranking');

        $DB->insert_record('assignsubmission_rankingsub', $data);
    }

    public function process_assignsubmission_exemplars($data) {
        global $DB;

        $data = (object) $data;
        $data->submissionid = $this->get_mappingid('submission', $data->submissionid);

        $DB->insert_record('assignsubmission_exemplars', $data);
    }

    public function process_assignsubmission_exclusion_submission($data) {
        global $DB;
        $data->submissionid = $this->get_mappingid('submission', $data->submissionid);
        $DB->insert_record('assignsubmission_exclusion', $data);
    }

    public function process_assignsubmission_exclusion_comment($data) {
        global $DB;
        $data->entityid = $this->get_mappingid('assignsubmission_compsubs', $data->entityid);
        $DB->insert_record('assignsubmission_exclusion', $data);
    }

    public function process_assignsubmission_exclusion_judge($data) {
        global $DB;
        $data->entityid = $this->get_mappingid('user', $data->entityid);
        $DB->insert_record('assignsubmission_exclusion', $data);
    }

    public function process_assignsubmission_email($data) {
        global $DB;

        $data = (object) $data;
        $data->assignmentid = $this->get_new_parentid('assign');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        $DB->insert_record('assignsubmission_email', $data);
    }
}
