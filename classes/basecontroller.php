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

use assign_header;
use moodle_url;

abstract class basecontroller {
    /** @var \assign $assignment the assignment record that contains the global
     *              settings for this assign instance
     */
    protected $assignment;
    protected $renderer;
    protected $assignmentsettings;

    public function __construct(\assign $assignment) {
        $this->assignment = $assignment;
        $this->renderer = $this->assignment->get_renderer();
        $this->assignmentsettings = \assign_submission_comparativejudgement::getpluginsettings($this->assignment);
    }

    protected function getheader($title) {
        $settings = \assign_submission_comparativejudgement::getpluginsettings($this->assignment);
        // If there are non-trivial judging instructions then display them as an alert.
        $judgeinst = format_text(trim($settings->introduction));
        if ($judgeinst != '') {
            $judgeinst = \html_writer::div($judgeinst, 'alert alert-info');
        }

        $header = new assign_header($this->assignment->get_instance(),
                $this->assignment->get_context(),
                false,
                $this->assignment->get_course_module()->id,
                $title,
                $judgeinst);
        return $this->renderer->render($header);
    }

    protected function getfooter() {
        return $this->renderer->render_footer();
    }

    public function getinternallink($pluginaction, $extraparams = []) {
        $params = ['id'            => $this->assignment->get_course_module()->id,
                   'action'        => 'viewpluginpage',
                   'plugin'        => 'comparativejudgement',
                   'pluginsubtype' => 'assignsubmission',
                   'pluginaction'  => $pluginaction
        ];

        $params += $extraparams;

        $url = new moodle_url('/mod/assign/view.php', $params);
        return $url;
    }
}
