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
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/webservice/lib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_warnings;

class external extends external_api {

    public static function toggle_exclusion_parameters() {
        return new external_function_parameters(
            [
                'assignmentid' => new external_value(PARAM_INT, 'The id of the entity being toggled'),
                'entityid' => new external_value(PARAM_INT, 'The id of the entity being toggled'),
                'state' => new external_value(PARAM_BOOL, 'State to set to'),
                'entitytype' => new external_value(PARAM_INT, 'Type of entity'),
            ]
        );
    }

    public static function toggle_exclusion($assignmentid, $entityid, $state, $entitytype) {
        self::validate_parameters(self::toggle_exclusion_parameters(), [
            'assignmentid' => $assignmentid,
            'entityid' => $entityid,
            'state' => $state,
            'entitytype' => $entitytype
        ]);

        $exclusion = exclusion::get_record([
            'assignmentid' => $assignmentid,
            'entityid' => $entityid,
            'type' => $entitytype,
        ]);
        if ($state && $exclusion) {
            $exclusion->delete();
        } else if (!$state && !$exclusion) {
            $exclusion = new exclusion();
            $exclusion->set('assignmentid', $assignmentid);
            $exclusion->set('entityid', $entityid);
            $exclusion->set('type', $entitytype);
            $exclusion->save();
        }

        return [];
    }

    public static function toggle_exclusion_returns() {
        return new external_warnings();
    }
}
