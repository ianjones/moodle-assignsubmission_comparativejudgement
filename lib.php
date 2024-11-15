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

use assignsubmission_comparativejudgement\comparison;
use assignsubmission_comparativejudgement\comparisonmanager;
use core_files\conversion;

defined('MOODLE_INTERNAL') || die();

/**
 * Serves assignment submissions and other files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options - List of options affecting file serving.
 * @return bool false if file not found, does not return if found - just send the file
 */
function assignsubmission_comparativejudgement_pluginfile($course,
        $cm,
        context $context,
        $filearea,
        $args,
        $forcedownload,
        array $options = []) {
    global $DB, $CFG, $USER, $SESSION;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // We're comparative judgement as the component then putting the plugincomponent (that was actually want the file from
    // in the filearea param as that will always be submission_files.
    $filearea = explode('fileareadelim', $filearea);
    $component = $filearea[0];
    $filearea = $filearea[1];

    require_login($course, false, $cm);
    $itemid = (int) array_shift($args);
    $record = $DB->get_record('assign_submission',
            ['id' => $itemid],
            'id, userid, assignment, groupid',
            MUST_EXIST);
    $userid = $record->userid;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    $assign = new assign($context, $cm, $course);

    if ($assign->get_instance()->id != $record->assignment) {
        return false;
    }

    if ($assign->get_instance()->teamsubmission) {
        $submission = $assign->get_group_submission($userid, 0, false);
    } else {
        $submission = $assign->get_user_submission($userid, false);
    }

    if ($record->id !== $submission->id) {
        // Something dodgy is going on.
        return false;
    }

    $comparisonmanager = new comparisonmanager($USER->id, $assign);
    if (!is_siteadmin() && !$comparisonmanager->canuserjudge($USER->id, $assign)) {
        return false;
    }

    if ($submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
        return false;
    }

    $args[count($args) - 1] = urldecode(openssl_decrypt(base64_decode($args[count($args) - 1]), openssl_get_cipher_methods()[0],
            $SESSION->assignsubmission_comparativejudgement_key, 0, $SESSION->assignsubmission_comparativejudgement_iv));

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/$component/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        return false;
    }

    if (!in_array($file->get_mimetype(), comparison::$skipconversion)) {
        // In a perfect world this would have an adhoc task and js polling.
        $converter = new \core_files\converter();
        $conversion = $converter->start_conversion($file, 'pdf');

        $status = $conversion->get('status');
        if ($status !== conversion::STATUS_COMPLETE && $status !== conversion::STATUS_FAILED) {
            do {
                sleep(1);
                $converter->poll_conversion($conversion);
                $status = $conversion->get('status');
            } while ($status !== conversion::STATUS_COMPLETE && $status !== conversion::STATUS_FAILED);
        }

        if ($conversion->get('status') == conversion::STATUS_COMPLETE) {
            $file = $conversion->get_destfile();
        }
    }

    $pathparts = pathinfo($file->get_filename());
    $options['filename'] = 'comparativejudgement_' . $submission->id . '_' . $file->get_id() . '.' . $pathparts['extension'];
    send_stored_file($file, 0, 0, false, $options);
}
