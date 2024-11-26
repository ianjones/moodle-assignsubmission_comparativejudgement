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

use core\persistent;

class comparison extends persistent {
    const TABLE = 'assignsubmission_comp';
    const POSITION_LEFT = 1;
    const POSITION_RIGHT = 2;

    protected static function define_properties() {
        return [
                'assignmentid'              => [
                        'type' => PARAM_INT,
                ],
                'winningsubmission'         => [
                        'type' => PARAM_INT,
                ],
                'winningsubmissionposition' => [
                        'type' => PARAM_INT,
                ],
                'timetaken'                 => [
                        'type' => PARAM_INT,
                ],
        ];
    }

    public static function recordcomparison($assigmentid, $timetaken, $winner, $winnerposition, $loser, $winnercomments = '',
            $winnerformat = FORMAT_HTML, $losercomments = '', $loserformat = FORMAT_HTML) {
        global $DB;
        $comparison = new comparison();
        $comparison->set('assignmentid', $assigmentid);
        $comparison->set('timetaken', $timetaken);
        $comparison->set('winningsubmission', $winner);
        $comparison->set('winningsubmissionposition', $winnerposition);

        $comparison->save();

        $DB->insert_record('assignsubmission_compsubs',
                (object) ['judgementid' => $comparison->get('id'), 'submissionid' => $winner,
                    'comments' => $winnercomments, 'commentsformat' => $winnerformat]);
        $DB->insert_record('assignsubmission_compsubs',
                (object) ['judgementid' => $comparison->get('id'), 'submissionid' => $loser,
                    'comments' => $losercomments, 'commentsformat' => $loserformat]);
    }

    public static $skipconversion = [
            'application/pdf', 'text/html', 'image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',
            'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm',
            'video/quicktime', 'video/mpeg', 'video/mp4',
            'audio/mp3',
    ];
}
