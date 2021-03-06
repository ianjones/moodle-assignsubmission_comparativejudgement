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
 * This hack is intended for clustered sites that do not want
 * to use shared cachedir for component cache.
 *
 * This file needs to be called after any change in PHP files in dataroot,
 * that is before upgrade and install.
 *
 * @package   core
 * @copyright 2013 Petr Skoda (skodak)  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true); // This prevents reading of existing caches.
define('IGNORE_COMPONENT_CACHE', true);

require(__DIR__.'/../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$exampledecisions = file_get_contents("$CFG->dirroot/mod/assign/submission/comparativejudgement/docs/exampledecisions.csv");

$rhandler = new \local_rhandler\rhandler("/mod/assign/submission/comparativejudgement/lib/pipeablescript.R");
$rhandler->setinput($exampledecisions);
$rhandler->execute();

echo $rhandler->get('errors');
echo $rhandler->get('output');
