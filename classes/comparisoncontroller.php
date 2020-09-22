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

use assign_submission_comparativejudgement;
use assign_submission_plugin;
use assignsubmission_comparativejudgement\event\comparison_made;
use core_files\converter;
use moodle_url;
use stdClass;

class comparisoncontroller extends basecontroller {
    public function summary() {
        global $OUTPUT;

        return $OUTPUT->single_button($this->getinternallink('comparison'),
                get_string('docomparison', 'assignsubmission_comparativejudgement'), 'get');
    }

    public function view() {
        global $PAGE;
        $url = $this->getinternallink('comparison');
        $PAGE->set_url($url);

        $assignmentid = $this->assignment->get_instance()->id;
        $introviewed = get_user_preferences("assignsubmission_comparativejudgement_introviewed_$assignmentid", false);
        $settings = assign_submission_comparativejudgement::getpluginsettings($this->assignment);

        if (empty($settings->introduction) || $introviewed) {
            return $this->showcomparisonscreen();
        } else {
            set_user_preferences(["assignsubmission_comparativejudgement_introviewed_$assignmentid" => true]);
            return $this->showintro();
        }
    }

    private function embed_pdf($fullurl, $mimetype) {
        global $PAGE;

        $hash = random_bytes(8);
        $code = "<object id='$hash' data='$fullurl' type='$mimetype' width='800' height='1050'></object>";
        $PAGE->requires->js_init_call('M.util.init_maximised_embed', [$hash], true);

        return $code;
    }

    /**
     * @param $plugin
     * @param $submission
     * @param \assign_renderer $assign_renderer
     * @return string
     */
    private function getsubmissionplugincontents(assign_submission_plugin $plugin, stdClass $submission): string {
        $o = '';
        if ($plugin->is_enabled() && $plugin->is_visible() && $plugin->has_user_summary() &&
                (!$plugin->is_empty($submission) || !$plugin->allow_submissions())) {
            $displaymode = \assign_submission_plugin_submission::FULL;
            $pluginsubmission = new \assign_submission_plugin_submission($plugin,
                    $submission,
                    $displaymode,
                    $this->assignment->get_course_module()->id,
                    '', []);
            $assign_renderer = $this->assignment->get_renderer();

            if (!$plugin->is_empty($submission)) {
                $o = $assign_renderer->render($pluginsubmission);
            }
        }
        return $o;
    }

    private function getsubmissionpluginfiles(assign_submission_plugin $plugin, stdClass $submission): array {
        $o = [];
        if ($submission->userid > 0) {
            $user = \core_user::get_user($submission->userid);
        } else { // It's an exemplar...
            $user = (object) ['id' => $submission->userid];
        }

        $all_assignsubmission_file_pluginfiles_embeddable[$plugin->get_type()] = true;
        if ($plugin->is_enabled() && $plugin->is_visible() && $plugin->has_user_summary() &&
                (!$plugin->is_empty($submission) || !$plugin->allow_submissions())) {
            $plugincomponent = $plugin->get_subtype() . '_' . $plugin->get_type();

            if (method_exists($plugin, 'get_files')) {
                $files = $plugin->get_files($submission, $user);
                $converter = new converter();

                foreach ($files as $key => $file) {
                    if (!is_a($file, 'stored_file')) {
                        continue;
                    }
                    $filename = $file->get_filename();
                    $array = explode('.', $filename);
                    $extension = array_pop($array);

                    $embeddable = in_array($file->get_mimetype(), comparison::$skipconversion);
                    $convertable = $converter->can_convert_format_to($extension, 'pdf');
                    if (!$embeddable && !$convertable) {
                        $all_assignsubmission_file_pluginfiles_embeddable = false;
                    }

                    $url = moodle_url::make_pluginfile_url($this->assignment->get_context()->id,
                            'assignsubmission_comparativejudgement',
                            $plugincomponent . 'fileareadelim' . $file->get_filearea(), $file->get_itemid(), $file->get_filepath(),
                            $file->get_filename());

                    if ($embeddable) {
                        $mimetype = $file->get_mimetype();
                    } else if ($convertable) {
                        $mimetype = 'application/pdf';
                    }

                    if (strpos($mimetype, 'image/') === 0) {
                        $contents = \html_writer::div(\html_writer::img($url, $filename, ['class' => 'w-100']));
                    } else {
                        $contents = $this->embed_pdf($url, $mimetype);
                    }

                    $o[] = (object) ['filename' => $filename, 'embed' => $this->scrambleurls($contents)];
                }
            }
        }
        return [$all_assignsubmission_file_pluginfiles_embeddable, $o];
    }

    /**
     * @return mixed
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function showcomparisonscreen() {
        global $USER, $OUTPUT, $PAGE;

        $comparisonmanager = new comparisonmanager($USER->id, $this->assignment);
        $commenthandler = new \assign_feedback_comments($this->assignment, 'comments');
        $showcomments = $commenthandler->is_enabled() || empty($this->assignmentsettings->enablecomments);

        $leftform = new comparisonform($this->getinternallink('comparison'), [
                'position'     => comparison::POSITION_LEFT,
                'showcomments' => $showcomments
        ], 'post', '', ['class' => 'comparisonformleft']);

        $rightform = new comparisonform($this->getinternallink('comparison'), [
                'position'     => comparison::POSITION_RIGHT,
                'showcomments' => $showcomments
        ], 'post', '', ['class' => 'comparisonformright']);

        $winnersubmitted = optional_param('position', false, PARAM_INT);
        if ($winnersubmitted == comparison::POSITION_RIGHT) {
            $data = $rightform->get_data();
        } else if ($winnersubmitted == comparison::POSITION_LEFT) {
            $data = $leftform->get_data();
        } else {
            $data = false;
        }

        if ($data) {
            if ($showcomments) {
                $winnerprop = "comments_winner_" . $data->position;
                $winnercomments = $data->$winnerprop['text'];
                $winnerformat = $data->$winnerprop['format'];
                $loserprop = "comments_loser_" . $data->position;
                $losercomments = $data->$loserprop['text'];
                $loserformat = $data->$loserprop['format'];
            } else {
                $winnercomments = '';
                $winnerformat = FORMAT_HTML;
                $losercomments = '';
                $loserformat = FORMAT_HTML;
            }

            comparison::recordcomparison($this->assignment->get_instance()->id, time() - $data->starttime, $data->winner,
                    $data->position,
                    $data->loser,
                    $winnercomments,
                    $winnerformat,
                    $losercomments,
                    $loserformat

            );

            comparison_made::create([
                    'relateduserid' => $USER->id,
                    'objectid'      => $this->assignment->get_course_module()->id,
                    'context'       => $this->assignment->get_context()
            ])->trigger();

            redirect($this->getinternallink('comparison'));
        }

        $finish = optional_param('finish', false, PARAM_INT);
        if ($finish) {
            redirect(new moodle_url('/mod/assign/view.php', ['id' => $this->assignment->get_course_module()->id]));
        }

        $submissions = $comparisonmanager->getpairtojudge();

        if (!$submissions) {
            redirect(new moodle_url('/mod/assign/view.php', ['id' => $this->assignment->get_course_module()->id]));
        }

        $submissionsunkeyed = array_values($submissions);

        $renderable = [];
        $renderable['header'] = $this->getheader(get_string('docomparison', 'assignsubmission_comparativejudgement'));
        $renderable['submissions'] = [];

        $position = comparison::POSITION_LEFT;
        foreach ($submissions as $submission) {
            $pluginsusedinsubmission = 0;

            $submissioncontents = [];
            $submissionfiles = [];
            foreach ($this->assignment->get_submission_plugins() as $plugin) {
                $plugincomponent = $plugin->get_subtype() . '_' . $plugin->get_type();
                $getsubmissionplugincontents = $this->getsubmissionplugincontents($plugin, $submission);

                if (empty($getsubmissionplugincontents)) {
                    continue;
                }

                if ($plugincomponent == 'assignsubmission_file') { // All other plugins we rely on the user summary.
                    list($all_assignsubmission_file_pluginfiles_embeddable, $pluginsubmissionfiles) =
                            $this->getsubmissionpluginfiles($plugin, $submission);

                    if (!empty($pluginsubmissionfiles)) {
                        $submissionfiles[] = (object) ['type'    => $plugin->get_name(),
                                                       'content' => $pluginsubmissionfiles];
                    }

                    if ($all_assignsubmission_file_pluginfiles_embeddable) {
                        $pluginsusedinsubmission++;
                        continue; // If all files can be embedded then we don't display the user summary for the files plugin.
                    }
                }

                $getsubmissionplugincontents = preg_replace('/pluginfile.php\/([\d]+)\/([a-z_]+)\/([a-z_]+)/',
                        'pluginfile.php/$1/assignsubmission_comparativejudgement/' . $plugincomponent . 'fileareadelim' . '$3',
                        $getsubmissionplugincontents);

                $submissioncontents[] = (object) ['type'    => $plugin->get_name(),
                                                  'content' => $this->scrambleurls($getsubmissionplugincontents)];
                $pluginsusedinsubmission++;
            }

            $renderable['submissions'][] =
                    (object) ['position'            => $position, 'contents' => $submissioncontents, 'files' => $submissionfiles,
                              'multiplepluginsused' => $pluginsusedinsubmission > 1];

            $position = comparison::POSITION_RIGHT;
        }

        $now = time();

        $default_values = [
                'winner'    => $submissionsunkeyed[0]->id,
                'loser'     => $submissionsunkeyed[1]->id,
                'starttime' => $now,
                'position'  => comparison::POSITION_LEFT
        ];
        $leftform->set_data($default_values);

        $default_values = [
                'winner'    => $submissionsunkeyed[1]->id,
                'loser'     => $submissionsunkeyed[0]->id,
                'starttime' => $now,
                'position'  => comparison::POSITION_RIGHT
        ];
        $rightform->set_data($default_values);

        $PAGE->requires->js_call_amd('assignsubmission_comparativejudgement/judge', 'init');

        $renderable['buttonleft'] = \html_writer::tag('button', get_string('left', 'assignsubmission_comparativejudgement'),
                ['class' => 'btn btn-primary comparisonbuttonleft']);
        $renderable['buttonleftbottom'] = $leftform->render();

        $renderable['buttonright'] = \html_writer::tag('button', get_string('right', 'assignsubmission_comparativejudgement'),
                ['class' => 'btn btn-primary comparisonbuttonright']);
        $renderable['buttonrightbottom'] = $rightform->render();

        if (!$comparisonmanager->redirectusertojudge()) {
            $finish = $this->getinternallink('comparison');
            $finish->params(['finish' => true]);
            $renderable['buttonfinish'] = $OUTPUT->single_button($finish,
                    get_string('stopjudging', 'assignsubmission_comparativejudgement'));
        } else if (!empty($this->assignmentsettings->minjudgementsperuser)) {
            $comparisoncount =
                    comparison::count_records(['usermodified' => $USER->id,
                                               'assignmentid' => $this->assignment->get_instance()->id]);
            if (empty($comparisoncount)) {
                $comparisoncount = 0;
            }
            $renderable['buttonfinish'] = $OUTPUT->single_button('',
                    get_string('comparisonprogress', 'assignsubmission_comparativejudgement',
                            ['number' => $comparisoncount + 1, 'required' => $this->assignmentsettings->minjudgementsperuser]), 'get',
                            ['disabled' => 'disabled']);
        }

        $renderable['footer'] = $this->getfooter();

        return $this->renderer->render_from_template('assignsubmission_comparativejudgement/comparison', (object) $renderable);
    }

    private function showintro() {
        $settings = assign_submission_comparativejudgement::getpluginsettings($this->assignment);

        $o = $this->getheader(get_string('docomparison', 'assignsubmission_comparativejudgement'));
        $o .= \html_writer::div($settings->introduction, 'introtojudging');
        $o .= \html_writer::link($this->getinternallink('comparison'), get_string('continue'), ['class' => 'btn btn-primary']);
        $o .= $this->getfooter();

        return $o;
    }

    private function scrambleurls($string) {
        global $SESSION;

        if (!isset($SESSION->assignsubmission_comparativejudgement_key)) {
            $SESSION->assignsubmission_comparativejudgement_key = openssl_random_pseudo_bytes(8);
        }
        if (!isset($SESSION->assignsubmission_comparativejudgement_iv)) {
            $SESSION->assignsubmission_comparativejudgement_iv = openssl_random_pseudo_bytes(16);
        }

        $matches = [];
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $string, $matches);
        foreach ($matches[0] as $match) {
            if (strpos($match, 'pluginfile.php') === false) {
                continue;
            }
            $explode = explode('/', $match);
            $filenameraw = $explode[count($explode) - 1];
            $filenameraw = explode('?', $filenameraw)[0];
            $encryptedfilename = openssl_encrypt($filenameraw, openssl_get_cipher_methods()[0],
                    $SESSION->assignsubmission_comparativejudgement_key, 0, $SESSION->assignsubmission_comparativejudgement_iv);
            $filename = base64_encode($encryptedfilename);

            $string = str_replace($filenameraw, $filename, $string);
            $string = str_replace(urldecode($filenameraw), get_string('userupload', 'assignsubmission_comparativejudgement'),
                    $string);
        }

        return $string;
    }
}