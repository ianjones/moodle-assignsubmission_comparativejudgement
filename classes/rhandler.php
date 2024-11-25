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

use moodle_exception;
use function escapeshellarg;

class rhandler {
    private $input;
    private $output;
    private $errors;
    private $returnvalue;
    private $absolutepathtoscript;

    public static function init_for_phpunit() {
        global $CFG;
        if (!empty($CFG->phpunit_assignsubmission_comparativejudgement_pathtorscript)) {
            set_config(
                'pathtorscript',
                $CFG->phpunit_assignsubmission_comparativejudgement_pathtorscript,
                'assignsubmission_comparativejudgement'
            );
        }
    }

    public function __construct($absolutepathtoscript) {
        $this->absolutepathtoscript = $absolutepathtoscript;
    }

    public function setinput($input) {
        $this->input = $input;
    }

    public function get($val) {
        if (!isset($this->returnvalue)) {
            throw new moodle_exception('Call execute before fetching values');
        }

        if (in_array($val, ['output', 'errors', 'returnvalue'])) {
            return $this->$val;
        }
    }

    public function execute() {
        $descriptorspec = [
                0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
                1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
                2 => ["pipe", "w"], // stderr is a file to write to
        ];

        $absolutepathtoscript = escapeshellarg($this->absolutepathtoscript);
        $rscript = escapeshellarg(get_config('assignsubmission_comparativejudgement', 'pathtorscript'));

        if (empty($rscript)) {
            throw new moodle_exception('Set path to Rscript');
        }

        $cmd = "$rscript $absolutepathtoscript";

        $sshproxy = get_config('assignsubmission_comparativejudgement', 'sshproxy');
        if (!empty($sshproxy)) {
            $cmd = $sshproxy . ' "' . $cmd . '"';
        }

        $process = proc_open($cmd, $descriptorspec, $pipes, sys_get_temp_dir());

        if (is_resource($process)) {
            if (isset($this->input)) {
                fwrite($pipes[0], $this->input);
                fclose($pipes[0]);
            }

            $this->output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $this->errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $this->returnvalue = proc_close($process);
        }

        return $this->returnvalue;
    }
}
