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

use local_certification\event\judgerequestemail_sent;

class judgerequestemail extends \core\persistent {
    const TABLE = 'assignsubmission_email';

    protected static function define_properties() {
        return [
                'assignmentid' => [
                        'type' => PARAM_INT,
                ],
                'delay'        => [
                        'type'    => PARAM_INT,
                        'default' => WEEKSECS * 6
                ],
                'subject'      => [
                        'type'    => PARAM_TEXT,
                        'default' => get_string('subjectdefault', 'assignsubmission_comparativejudgement')
                ],
                'body'         => [
                        'type'    => PARAM_TEXT,
                        'default' => get_string('bodydefault', 'assignsubmission_comparativejudgement')
                ]
        ];
    }

    /**
     * @return judgerequestemail[]
     * @throws \dml_exception
     */
    public static function get_all_judgerequestemails_by_id() {
        $certs = self::get_records();
        $retval = [];

        foreach ($certs as $cert) {
            $retval[$cert->get('id')] = $cert;
        }

        return $retval;
    }
}