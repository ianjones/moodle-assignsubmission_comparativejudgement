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

defined('MOODLE_INTERNAL') || die();

use assignsubmission_comparativejudgement\comparisoncontroller;
use assignsubmission_comparativejudgement\comparisonmanager;
use assignsubmission_comparativejudgement\exemplarcontroller;
use assignsubmission_comparativejudgement\judgerequestemailcontroller;
use assignsubmission_comparativejudgement\managecomparisoncommentscontroller;
use assignsubmission_comparativejudgement\managecomparisonscontroller;
use assignsubmission_comparativejudgement\managejudgescontroller;
use assignsubmission_comparativejudgement\managesubmissionscontroller;

require_once($CFG->dirroot . '/comment/lib.php');
require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

class assign_submission_comparativejudgement extends assign_submission_plugin {
    const FAKEROLE_GRADABLE_USERS = -10;
    const FAKEROLE_ASSIGNMENT_SUBMITTED = -20;

    /**
     * Get the name of the online comparative judgement plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_comparativejudgement');
    }

    public function get_config_or_default($key) {
        $defaults = [
                'minjudgementsperuser'       => 10,
                'maxjudgementsperuser'       => '',
                'minjudgementspersubmission' => 5,
                'judgementswhileeditable'    => true,
                'enablecomments'             => true,
                'judges'                     => self::FAKEROLE_ASSIGNMENT_SUBMITTED,
                'introduction'               => '',
                'allowrepeatcomparisons'    => true,
                'allowcompareexemplars'    => true,
        ];

        $config = $this->get_config($key);

        if ($config === false && isset($defaults[$key])) {
            $config = $defaults[$key];
        }

        return $config;
    }

    public function get_settings(MoodleQuickForm $mform) {
        $mform->addElement(
            'advcheckbox',
            'comparativejudgement_allowcompareexemplars',
            get_string('comparativejudgement_allowcompareexemplars', 'assignsubmission_comparativejudgement')
        );
        $mform->addHelpButton(
            'comparativejudgement_allowcompareexemplars',
            'comparativejudgement_allowcompareexemplars',
            'assignsubmission_comparativejudgement'
        );
        $mform->setDefault(
            'comparativejudgement_allowcompareexemplars',
            $this->get_config_or_default('allowcompareexemplars')
        );

        $mform->addElement(
            'advcheckbox',
            'comparativejudgement_allowrepeatcomparisons',
            get_string('comparativejudgement_allowrepeatcomparisons', 'assignsubmission_comparativejudgement')
        );
        $mform->addHelpButton(
            'comparativejudgement_allowrepeatcomparisons',
            'comparativejudgement_allowrepeatcomparisons',
            'assignsubmission_comparativejudgement'
        );
        $mform->setDefault(
            'comparativejudgement_allowrepeatcomparisons',
            $this->get_config_or_default('allowrepeatcomparisons')
        );

        $mform->addElement(
            'text',
            'comparativejudgement_minjudgementsperuser',
            get_string('minjudgementsperuser', 'assignsubmission_comparativejudgement')
        );
        $mform->setDefault('comparativejudgement_minjudgementsperuser', $this->get_config_or_default('minjudgementsperuser'));
        $mform->setType('comparativejudgement_minjudgementsperuser', PARAM_INT);
        $mform->hideIf('comparativejudgement_minjudgementsperuser', 'assignsubmission_comparativejudgement_enabled');

        $mform->addElement(
            'text',
            'comparativejudgement_maxjudgementsperuser',
            get_string('maxjudgementsperuser', 'assignsubmission_comparativejudgement')
        );
        $mform->setDefault('comparativejudgement_maxjudgementsperuser', $this->get_config_or_default('maxjudgementsperuser'));
        $mform->setType('comparativejudgement_maxjudgementsperuser', PARAM_INT);
        $mform->hideIf('comparativejudgement_maxjudgementsperuser', 'assignsubmission_comparativejudgement_enabled');

        $mform->addElement(
            'text',
            'comparativejudgement_minjudgementspersubmission',
            get_string('minjudgementspersubmission', 'assignsubmission_comparativejudgement')
        );
        $mform->setDefault(
            'comparativejudgement_minjudgementspersubmission',
            $this->get_config_or_default('minjudgementspersubmission')
        );
        $mform->setType('comparativejudgement_minjudgementspersubmission', PARAM_INT);
        $mform->hideIf('comparativejudgement_minjudgementspersubmission', 'assignsubmission_comparativejudgement_enabled');

        $commenthandler = new assign_feedback_comments($this->assignment, 'comments');
        if ($commenthandler->is_enabled()) {
            $mform->addElement(
                'advcheckbox',
                'comparativejudgement_enablecomments',
                '',
                get_string('comparativejudgement_enablecomments', 'assignsubmission_comparativejudgement')
            );
            $mform->setDefault(
                'comparativejudgement_enablecomments',
                $this->get_config_or_default('enablecomments')
            );
        }

        $mform->addElement(
            'date_time_selector',
            'comparativejudgement_judgementstartdate',
            get_string('judgementstartdate', 'assignsubmission_comparativejudgement'),
            ['optional' => true]
        );
        $mform->setDefault('comparativejudgement_judgementstartdate', $this->get_config_or_default('judgementstartdate'));
        $mform->hideIf('comparativejudgement_judgementstartdate', 'assignsubmission_comparativejudgement_enabled');

        $mform->addElement(
            'advcheckbox',
            'comparativejudgement_judgementswhileeditable',
            get_string('comparativejudgement_judgementswhileeditable', 'assignsubmission_comparativejudgement')
        );
        $mform->addHelpButton('comparativejudgement_judgementswhileeditable', 'quickgrading', 'assign');
        $mform->setDefault(
            'comparativejudgement_judgementswhileeditable',
            $this->get_config_or_default('judgementswhileeditable')
        );
        $mform->hideIf('comparativejudgement_judgementswhileeditable', 'assignsubmission_comparativejudgement_enabled');

        $mform->addElement(
            'textarea',
            'comparativejudgement_introduction',
            get_string('comparativejudgement_introduction', 'assignsubmission_comparativejudgement'),
            ['rows' => 10,
            'cols' => 57]
        );
        $mform->setType('comparativejudgement_introduction', PARAM_TEXT);
        $mform->setDefault('comparativejudgement_introduction', $this->get_config_or_default('introduction'));
        $mform->hideIf('comparativejudgement_introduction', 'assignsubmission_comparativejudgement_enabled');

        $options = [];
        $options[self::FAKEROLE_GRADABLE_USERS] = get_string('fakerole_gradable_users', 'assignsubmission_comparativejudgement');
        $options[self::FAKEROLE_ASSIGNMENT_SUBMITTED] =
                get_string('fakerole_assignment_submitted', 'assignsubmission_comparativejudgement');

        $context = $this->assignment->get_context();
        if (!$context) {
            $context = $this->assignment->get_course_context();
        }
        $options += get_viewable_roles($context);

        $mform->addElement(
            'select',
            'comparativejudgement_judges',
            get_string('judges', 'assignsubmission_comparativejudgement'),
            $options,
            ['multiple' => true]
        );
        $mform->setType('comparativejudgement_judges', PARAM_INT);
        $mform->setDefault('comparativejudgement_judges', $this->get_config_or_default('judges'));
        $mform->hideIf('comparativejudgement_judges', 'assignsubmission_comparativejudgement_enabled');
    }

    public function save_settings(stdClass $formdata) {
        $vals = (array) $formdata;

        foreach ($vals as $key => $value) {
            $split = explode('_', $key);
            if ($split[0] == 'comparativejudgement') {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $this->set_config($split[1], $value);
            }
        }

        return true;
    }

    public function delete_instance() {
        global $DB;

        $DB->delete_records(
            'assignsubmission_comp',
            ['assignmentid' => $this->assignment->get_instance()->id]
        );
        $DB->delete_records(
            'assignsubmission_ranking',
            ['assignmentid' => $this->assignment->get_instance()->id]
        );
        $DB->delete_records(
            'assignsubmission_email',
            ['assignmentid' => $this->assignment->get_instance()->id]
        );
        $DB->delete_records(
            'assignsubmission_exclusion',
            ['assignmentid' => $this->assignment->get_instance()->id]
        );

        $DB->delete_records_subquery(
            'assignsubmission_compsubs',
            'submissionid',
            'id',
            "select id from {assign_submission} where assignment = :assignmentid",
            ['assignmentid' => $this->assignment->get_instance()->id]
        );

        $DB->delete_records_subquery(
            'assignsubmission_rankingsub',
            'submissionid',
            'id',
            "select id from {assign_submission} where assignment = :assignmentid",
            ['assignmentid' => $this->assignment->get_instance()->id]
        );

        $DB->delete_records_subquery(
            'assignsubmission_exemplars',
            'submissionid',
            'id',
            "select id from {assign_submission} where assignment = :assignmentid",
            ['assignmentid' => $this->assignment->get_instance()->id]
        );

        return true;
    }

    public function view_header() {
        global $USER, $CFG, $OUTPUT;

        $o = '';

        $o .= $OUTPUT->container_start('comparativejudgement');
        $o .= $OUTPUT->heading(get_string('pluginname', 'assignsubmission_comparativejudgement'), 3);
        $o .= $OUTPUT->box_start('boxaligncenter comparativejudgementbuttons');

        $comparisonmanager = new comparisonmanager($USER->id, $this->assignment);
        if ($comparisonmanager->canuserjudge() && $comparisonmanager->getpairtojudge()) {
            $controller = new comparisoncontroller($this->assignment);

            $onlyrolesgradable = true;
            $userroles = get_user_roles($this->assignment->get_context(), $USER->id);

            $gradebookroles = explode(',', $CFG->gradebookroles);
            foreach ($userroles as $role) {
                if (!in_array($role->roleid, $gradebookroles)) {
                    $onlyrolesgradable = false;
                }
            }

            if ($onlyrolesgradable && $comparisonmanager->redirectusertojudge()) {
                redirect($controller->getinternallink('comparison'));
            }

            $o .= $controller->summary();
        }

        if (has_capability('assignsubmission/comparativejudgement:manageexemplars', $this->assignment->get_context())) {
            $exemplarcontroller = new exemplarcontroller($this->assignment);
            $o .= $exemplarcontroller->summary();
        }

        if (has_capability('mod/assign:grade', $this->assignment->get_context())) {
            $controller = new managejudgescontroller($this->assignment);
            $o .= $controller->summary();

            $controller = new managecomparisoncommentscontroller($this->assignment);
            $o .= $controller->summary();

            $controller = new managesubmissionscontroller($this->assignment);
            $o .= $controller->summary();

            $controller = new managecomparisonscontroller($this->assignment);
            $o .= $controller->summary();
        }

        if (has_capability('assignsubmission/comparativejudgement:manageemails', $this->assignment->get_context())) {
            $controller = new judgerequestemailcontroller($this->assignment);
            $o .= $controller->summary();
        }

        $o .= $OUTPUT->box_end();
        $o .= $OUTPUT->container_end();

        return $o;
    }

    public function view_page($action) {
        global $USER;

        if ($action == 'addexemplar') {
            require_capability('assignsubmission/comparativejudgement:manageexemplars', $this->assignment->get_context());
            $exemplar = new exemplarcontroller($this->assignment);
            return $exemplar->view();
        }

        if ($action == 'manageexemplars') {
            require_capability('assignsubmission/comparativejudgement:manageexemplars', $this->assignment->get_context());
            $exemplar = new exemplarcontroller($this->assignment);
            return $exemplar->viewmanageexemplars();
        }

        if ($action == 'deleteexemplar') {
            require_capability('assignsubmission/comparativejudgement:manageexemplars', $this->assignment->get_context());
            $exemplar = new exemplarcontroller($this->assignment);
            return $exemplar->viewdelete();
        }
        if ($action == 'comparison') {
            $comparisonmanager = new comparisonmanager($USER->id, $this->assignment);
            if (!$comparisonmanager->canuserjudge()) {
                redirect(new moodle_url('/mod/assign/view.php', ['id' => $this->assignment->get_course_module()->id]));
            }
            $controller = new comparisoncontroller($this->assignment);
            return $controller->view();
        }
        if ($action == 'managejudges') {
            require_capability('mod/assign:grade', $this->assignment->get_context());
            $controller = new managejudgescontroller($this->assignment);
            return $controller->view();
        }
        if ($action == 'managecomparisons') {
            require_capability('mod/assign:grade', $this->assignment->get_context());
            $controller = new managecomparisonscontroller($this->assignment);
            return $controller->view();
        }
        if ($action == 'deletecomparison') {
            require_capability('mod/assign:grade', $this->assignment->get_context());
            $controller = new managecomparisonscontroller($this->assignment);
            return $controller->viewdelete();
        }
        if ($action == 'managecomparisoncomments') {
            require_capability('mod/assign:grade', $this->assignment->get_context());
            $controller = new managecomparisoncommentscontroller($this->assignment);
            return $controller->view();
        }
        if ($action == 'managesubmissions') {
            require_capability('mod/assign:grade', $this->assignment->get_context());
            $controller = new managesubmissionscontroller($this->assignment);
            return $controller->view();
        }
        if ($action == 'judgerequestemail') {
            require_capability('assignsubmission/comparativejudgement:manageemails', $this->assignment->get_context());
            $controller = new judgerequestemailcontroller($this->assignment);
            return $controller->view();
        }
        if ($action == 'judgerequestemailcreate') {
            require_capability('assignsubmission/comparativejudgement:manageemails', $this->assignment->get_context());
            $controller = new judgerequestemailcontroller($this->assignment);
            return $controller->viewcreateedit();
        }
        if ($action == 'deletejudgerequestemail') {
            require_capability('assignsubmission/comparativejudgement:manageemails', $this->assignment->get_context());
            $controller = new judgerequestemailcontroller($this->assignment);
            return $controller->viewdelete();
        }

        return parent::view_page($action);
    }

    private static $submissionpluginsettings = [];
    private static $submissionplugins = [];

    /**
     * return subtype name of the plugin
     *
     * @return assign_submission_comparativejudgement
     * @var assign $assignment
     */
    public static function getplugin(assign $assignment) {
        if (PHPUNIT_TEST) {
            self::$submissionplugins = [];
        }

        if (!isset(self::$submissionplugins[$assignment->get_instance()->id])) {
            self::$submissionplugins[$assignment->get_instance()->id] = false;

            foreach ($assignment->get_submission_plugins() as $plugin) {
                if (!is_a($plugin, 'assign_submission_comparativejudgement')) {
                    continue;
                }

                self::$submissionplugins[$assignment->get_instance()->id] = $plugin;
            }
        }
        return self::$submissionplugins[$assignment->get_instance()->id];
    }

    /**
     * return subtype name of the plugin
     *
     * @return stdClass
     * @var assign $assignment
     */
    public static function getpluginsettings(assign $assignment) {
        if (PHPUNIT_TEST) {
            self::$submissionpluginsettings = [];
        }

        if (!isset(self::$submissionpluginsettings[$assignment->get_instance()->id])) {
            $plugin = self::getplugin($assignment);

            $config = $plugin->get_config();

            if (isset($config->judges)) {
                $config->judges = explode(',', $config->judges);
            }

            self::$submissionpluginsettings[$assignment->get_instance()->id] = $config;
        }
        return self::$submissionpluginsettings[$assignment->get_instance()->id];
    }
}
