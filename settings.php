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

$settings->add(new admin_setting_configcheckbox('assignsubmission_comparativejudgement/dofakecomparison',
                   new lang_string('dofakecomparison', 'assignsubmission_comparativejudgement'),
                   new lang_string('dofakecomparison', 'assignsubmission_comparativejudgement'), 0));


$settings->add(new admin_setting_configexecutable('assignsubmission_comparativejudgement/pathtorscript',
    new lang_string('pathtorscript', 'assignsubmission_comparativejudgement'), '',
    get_config('local_rhandler', 'pathtorscript')));

$settings->add(new admin_setting_configexecutable('assignsubmission_comparativejudgement/sshproxy',
    new lang_string('sshproxy', 'assignsubmission_comparativejudgement'), new lang_string('sshproxy_help', 'assignsubmission_comparativejudgement'),
    get_config('local_rhandler', 'sshproxy')));
