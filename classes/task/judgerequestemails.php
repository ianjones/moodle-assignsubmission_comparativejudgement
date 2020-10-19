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

namespace assignsubmission_comparativejudgement\task;

use assign;
use assignsubmission_comparativejudgement\comparisoncontroller;
use assignsubmission_comparativejudgement\comparisonmanager;
use context_module;
use core\message\message;
use core\task\scheduled_task;
use core_user;
use assignsubmission_comparativejudgement\event\judgerequestemail_sent;

defined('MOODLE_INTERNAL') || die();

class judgerequestemails extends scheduled_task {
    public function get_name() {
        return get_string('sendjudgerequestemails', 'assignsubmission_comparativejudgement');
    }

    public function execute() {
        $time = time();
        $laststarttime = get_config('assignsubmission_comparativejudgement', 'sendjudgerequeststarttime');
        $this->get_emails_to_send($laststarttime, $time);
        set_config('sendjudgerequeststarttime', $time, 'assignsubmission_comparativejudgement');
    }

    public function get_emails_to_send($lastrun, $thisrun) {
        global $DB, $USER;

        $enddatefrag = "(CASE
           WHEN c.value IS NOT NULL AND c.value > 0 THEN c.value
           WHEN a.cutoffdate IS NOT NULL AND a.cutoffdate > 0 THEN a.cutoffdate
           WHEN a.duedate IS NOT NULL AND a.duedate > 0 THEN a.duedate
           ELSE 0
           END
            ) + delay";

        $sql_emails_due = "SELECT e.*, cm.id as cmid, (CASE WHEN c.id is null THEN 0 ELSE 1 END) as judgementstartdateset
        FROM {assignsubmission_email} e
        INNER JOIN {assign} a on e.assignmentid = a.id
        INNER JOIN {course_modules} cm on cm.instance = a.id
        INNER JOIN {modules} m on cm.module = m.id and m.name = 'assign'
        LEFT JOIN {assign_plugin_config} c on c.assignment = a.id and plugin = 'comparativejudgement' and subtype = 'assignsubmission' and c.name = 'judgementstartdate'
        WHERE $enddatefrag <= :now and $enddatefrag > :last";

        $emailsdue = $DB->get_records_sql($sql_emails_due, ['now' => $thisrun, 'last' => $lastrun]);

        foreach ($emailsdue as $email) {
            $assign = new assign(context_module::instance($email->cmid), false, false);
            $comparisonmanager = new comparisonmanager($USER->id, $assign);
            $judges = $comparisonmanager->getalljudges();
            $judgesassoc = array_combine($judges, $judges);

            // For any with judgementstartdateset = 0 find users with extensions to exclude - they will get picked up at the end of their cutoff/due date.
            if (empty($email->judgementstartdateset)) {
                $overrides = $DB->get_records('assign_overrides', ['assignid' => $assign->get_instance()->id]);

                foreach ($overrides as $extension) {
                    if (!empty($extension->userid)) {
                        unset($judgesassoc[$extension->userid]);
                    } else if (!empty($extension->groupid)) {
                        $members = $assign->get_submission_group_members($extension->groupid, true, true);

                        foreach ($members as $member) {
                            unset($judgesassoc[$member->id]);
                        }
                    }
                }

                $extensions = $DB->get_records('assign_user_flags', ['assignment' => $assign->get_instance()->id]);
                foreach ($extensions as $extension) {
                    if (!empty($extension->extensionduedate && $extension->extensionduedate > $thisrun)) {
                        unset($judgesassoc[$extension->userid]);
                    }
                }
            }

            foreach ($judgesassoc as $judge) {
                $this->send_judgerequestemail($judge, $email, $assign);
            }
        }

        $enddatefrag = "(CASE
           WHEN c.value IS NOT NULL AND c.value > 0 THEN c.value
           WHEN ao.cutoffdate IS NOT NULL AND ao.cutoffdate > 0 THEN ao.cutoffdate
           WHEN ao.duedate IS NOT NULL AND ao.duedate > 0 THEN ao.duedate
           ELSE 0
           END
            ) + delay";

        $sql_emails_due_overrides = "SELECT e.*, cm.id as cmid, ao.groupid, ao.userid, (CASE WHEN c.id is null THEN 0 ELSE 1 END) as judgementstartdateset
        FROM {assignsubmission_email} e
        INNER JOIN {assign} a on e.assignmentid = a.id
        INNER JOIN {assign_overrides} ao on ao.assignid = a.id
        INNER JOIN {course_modules} cm on cm.instance = a.id
        INNER JOIN {modules} m on cm.module = m.id and m.name = 'assign'
        LEFT JOIN {assign_plugin_config} c on c.assignment = a.id and plugin = 'comparativejudgement' and subtype = 'assignsubmission' and c.name = 'judgementstartdate'
        WHERE $enddatefrag <= :now AND $enddatefrag > :last AND c.id is null";

        $emailsdue = $DB->get_records_sql($sql_emails_due_overrides, ['now' => $thisrun, 'last' => $lastrun]);

        foreach ($emailsdue as $email) {
            $assign = new assign(context_module::instance($email->cmid), false, false);

            if (!empty($email->userid)) {
                $this->send_judgerequestemail($email->userid, $email, $assign);
            } else if (!empty($email->groupid)) {
                $members = $assign->get_submission_group_members($email->groupid, true, true);

                foreach ($members as $member) {
                    $this->send_judgerequestemail($member->id, $email, $assign);
                }
            }
        }

        $sql_emails_due_overrides = "SELECT e.*, ao.userid, cm.id as cmid, (CASE WHEN c.id is null THEN 0 ELSE 1 END) as judgementstartdateset
        FROM {assignsubmission_email} e
        INNER JOIN {assign} a on e.assignmentid = a.id
        INNER JOIN {assign_user_flags} ao on ao.assignment = a.id
        INNER JOIN {course_modules} cm on cm.instance = a.id
        INNER JOIN {modules} m on cm.module = m.id and m.name = 'assign'
        LEFT JOIN {assign_plugin_config} c on c.assignment = a.id and plugin = 'comparativejudgement' and subtype = 'assignsubmission' and c.name = 'judgementstartdate'
        WHERE (extensionduedate + delay) <= :now AND (extensionduedate + delay) > :last AND c.id is null";

        $emailsdue = $DB->get_records_sql($sql_emails_due_overrides, ['now' => $thisrun, 'last' => $lastrun]);

        foreach ($emailsdue as $email) {
            $assign = new assign(context_module::instance($email->cmid), false, false);
            $this->send_judgerequestemail($email->userid, $email, $assign);
        }
    }

    private function send_judgerequestemail($userid, $email, assign $assign) {
        global $SITE, $CFG;

        $judgecomparisonmanager = new comparisonmanager($userid, $assign);

        if (!$judgecomparisonmanager->getpairtojudge()) {
            return false;
        }

        $cmid = $assign->get_course_module()->id;
        $user = core_user::get_user($userid);
        $noreplyuser = core_user::get_noreply_user();

        $body = $this->get_message_body($user, $email, $assign);

        $message = new message();
        $message->component = 'assignsubmission_comparativejudgement';
        $message->name = 'judgerequest';
        $message->userfrom = $noreplyuser;
        $message->userto = $user;
        $message->subject = $email->subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->smallmessage = $body;
        $message->notification = $CFG->wwwroot;
        $message->contexturl = new \moodle_url("$CFG->wwwroot/mod/assign/view.php", ['id' => $cmid]);
        $message->contexturlname = $assign->get_instance()->name;
        $message->replyto = $noreplyuser->email;

        $message->courseid = $SITE->id;

        message_send($message);

        judgerequestemail_sent::create([
                'relateduserid' => $user->id,
                'objectid'      => $cmid,
                'context'       => $assign->get_context()
        ])->trigger();
    }

    public function get_message_body($user, $email, \assign $assign) {
        global $CFG;

        $msg = $email->body;
        $cmid = $assign->get_course_module()->id;

        $controller = new comparisoncontroller($assign);

        // Replace placeholders with values.
        $msg = str_replace('[firstname]', $user->firstname, $msg);
        $msg = str_replace('[lastname]', $user->lastname, $msg);
        $msg = str_replace('[fullname]', fullname($user), $msg);
        $msg = str_replace('[assignurl]', new \moodle_url("$CFG->wwwroot/mod/assign/view.php", ['id' => $cmid]), $msg);
        $msg = str_replace('[judgeurl]', $controller->getinternallink('comparison'), $msg);
        $msg = str_replace('[assignname]', $assign->get_instance()->name, $msg);
        return $msg;
    }
}
