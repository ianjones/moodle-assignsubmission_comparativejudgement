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

class exclusion extends persistent {
    const TABLE = 'assignsubmission_exclusion';

    const EXCLUSION_TYPE_SUBMISSION = 10;
    const EXCLUSION_TYPE_JUDGE = 20;
    const EXCLUSION_TYPE_COMPARISONCOMMENT = 30;

    protected static function define_properties() {
        return [
                'type' => [
                        'type' => PARAM_INT,
                ],
                'entityid' => [
                        'type' => PARAM_INT,
                ],
        ];
    }
}