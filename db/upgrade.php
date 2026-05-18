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
 * Upgrade code for install
 *
 * @package   assignsubmission_comparativejudgement
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * upgrade this assignment instance - this function could be skipped but it will be needed later
 * @param int $oldversion The old version of the assign module
 * @return bool
 */
function xmldb_assignsubmission_comparativejudgement_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026052101) {
        // Define field hidegrader to be added to assign.
        $table = new xmldb_table('assignsubmission_compsubs');
        foreach (
            [
                     new xmldb_field('comments', XMLDB_TYPE_TEXT, null, null, null, null, null, 'submissionid'),
                     new xmldb_field('commentsformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'comments'),
                     new xmldb_field('commentpublished', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'comments'),
                 ] as $field
        ) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('assignsubmission_exclusion');
        $field = new xmldb_field('assignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('assignsubmission_comp');
        foreach (
            [
                     new xmldb_field('comments'),
                     new xmldb_field('commentsformat'),
                 ] as $field
        ) {
            // Conditionally launch drop field quizid.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        $table = new xmldb_table('assignsubmission_exclusion');
        foreach (
            [
                     new xmldb_field('entityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL),
                     new xmldb_field('assignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL),
                 ] as $field
        ) {
            $dbman->change_field_type($table, $field);
        }

        $index = new xmldb_index('assiexcl', XMLDB_INDEX_NOTUNIQUE, ['assignmentid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026052101, 'assignsubmission', 'comparativejudgement');
    }
    return true;
}
