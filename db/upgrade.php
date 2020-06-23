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
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * upgrade this assignment instance - this function could be skipped but it will be needed later
 * @param int $oldversion The old version of the assign module
 * @return bool
 */
function xmldb_assignsubmission_comparativejudgement_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019111811) {
        // Define field hidegrader to be added to assign.
        $table = new xmldb_table('assignsubmission_compsubs');

        $field = new xmldb_field('comments', XMLDB_TYPE_TEXT, null, null, null, null, null, 'submissionid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('commentsformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'comments');

        // Conditionally launch add field commentsformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('assignsubmission_comp');
        $field = new xmldb_field('comments');

        // Conditionally launch drop field quizid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('commentsformat');

        // Conditionally launch drop field quizid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Assignment savepoint reached.
        upgrade_plugin_savepoint(true, 2019111811, 'assignsubmission', 'comparativejudgement');
    }

    if ($oldversion < 2019111812) {
        // Define field hidegrader to be added to assign.
        $table = new xmldb_table('assignsubmission_compsubs');

        $field = new xmldb_field('commentpublished', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'comments');

        // Conditionally launch add field commentsformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assignment savepoint reached.
        upgrade_plugin_savepoint(true, 2019111812, 'assignsubmission', 'comparativejudgement');
    }

    return true;
}
