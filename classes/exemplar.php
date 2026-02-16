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
use dml_exception;
use stdClass;

class exemplar extends persistent {
    const TABLE = 'assignsubmission_exemplars';

    /**
     * @param assign $assignment
     * @return int|mixed
     * @throws dml_exception
     */
    public static function getnextuserid(assign $assignment) {
        global $DB;

        $nextuserid = $DB->get_field_sql(
            'select min(userid) from {assign_submission} where assignment = :assignment',
            ['assignment' => $assignment->get_instance()->id]
        );
        if ($nextuserid > 0) {
            $nextuserid = -1;
        } else {
            $nextuserid -= 1;
        }
        return $nextuserid;
    }

    protected static function define_properties() {
        return [
                'submissionid' => [
                        'type' => PARAM_INT,
                ],
                'title'        => [
                        'type'    => PARAM_TEXT,
                        'default' => '',
                ],
        ];
    }

    public static function save_exemplar_submission(stdClass $data, assign $assignment, $submission, &$notices) {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        if (!$submission) {
            $nextuserid = self::getnextuserid($assignment);
            $submission = $assignment->get_user_submission($nextuserid, true);

            if ($assignment->new_submission_empty($data)) {
                $notices[] = get_string('submissionempty', 'mod_assign');
                return false;
            }
        }

        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;

        $pluginerror = false;
        self::toggleturnitinprocessing(false);
        foreach ($assignment->get_submission_plugins() as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                if (!$plugin->save($submission, $data)) {
                    $notices[] = $plugin->get_error();
                    $pluginerror = true;
                }
            }
        }
        self::toggleturnitinprocessing(true);

        $allempty = $assignment->submission_empty($submission);
        if ($pluginerror || $allempty) {
            if ($allempty) {
                $notices[] = get_string('submissionempty', 'mod_assign');
            }
            return false;
        }

        $DB->update_record('assign_submission', $submission);

        $exempler = self::get_record(['submissionid' => $submission->id]);
        if (!$exempler) {
            $exempler = new exemplar();
            $exempler->set('submissionid', $submission->id);
        }
        $exempler->set('title', $data->title);
        $exempler->save();

        $trans->allow_commit();
    }

    private static function toggleturnitinprocessing(bool $state) {
        global $CFG;

        static $oldvalue = null;

        if (!isset($CFG->forced_plugin_settings)) {
            $CFG->forced_plugin_settings = [];
        }
        if (!isset($CFG->forced_plugin_settings['plagiarism_turnitin'])) {
            $CFG->forced_plugin_settings['plagiarism_turnitin'] = [];
        }

        if ($state === false) {
            if (!isset($oldvalue)) {
                $oldvalue = $CFG->forced_plugin_settings['plagiarism_turnitin']['plagiarism_turnitin_mod_assign'] ?? null;
            }
            $CFG->forced_plugin_settings['plagiarism_turnitin']['plagiarism_turnitin_mod_assign'] = false;
        } else if (isset($oldvalue)) {
            $CFG->forced_plugin_settings['plagiarism_turnitin']['plagiarism_turnitin_mod_assign'] = $oldvalue;
        } else {
            unset($CFG->forced_plugin_settings['plagiarism_turnitin']['plagiarism_turnitin_mod_assign']);
        }
    }

    public static function get_exemplarsbyassignmentid($assignmentid) {
        global $DB;

        $records =
                $DB->get_records_sql('select asex.*
                                            from {assignsubmission_exemplars} asex
                                            inner join {assign_submission} subs on asex.submissionid = subs.id
                                            where subs.assignment = :assignmentid', ['assignmentid' => $assignmentid]);
        $instances = [];

        foreach ($records as $record) {
            $newrecord = new static(0, $record);
            $instances[] = $newrecord;
        }
        return $instances;
    }

    public function delete_exemplar_submission(assign $assignment) {
        global $DB;

        $exemplar = self::get_record(['id' => $this->get('id')]);
        $submission = $DB->get_record('assign_submission', ['id' => $exemplar->get('submissionid'), 'latest' => 1]);

        $plugins = $assignment->get_submission_plugins();
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $plugin->remove($submission);
            }
        }

        $DB->delete_records('assign_submission', ['id' => $exemplar->get('submissionid')]);

        $exemplar->delete();
    }
}
