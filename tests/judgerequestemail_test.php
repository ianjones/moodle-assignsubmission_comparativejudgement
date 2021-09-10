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

use assignsubmission_comparativejudgement\comparisonmanager;
use assignsubmission_comparativejudgement\judgerequestemail;

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * @group assignsubmission_comparativejudgement
 */
class assignsubmission_comparativejudgement_judgerequestemail_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    public function setUp() :void {
        global $CFG;

        $CFG->enablecompletion = true;
        $buffer = new progress_trace_buffer(new text_progress_trace(), false);
        $this->resetAfterTest(true);
    }

    public function testexecute() {
        $time = time();
        set_config('sendjudgerequeststarttime', $time - 1, 'assignsubmission_comparativejudgement');

        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');

        $sink = $this->redirectEmails();

        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages);
    }

    public function test_emails_submitted() {
        global $DB;

        list($teacher, $editingteacher, $student, $secondassign, $plugin, $course) = $this->setupstandardscenario();
        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');
        $email = new judgerequestemail();
        $email->set('delay', 1);
        $email->set('subject', 'hello');
        $email->set('body', 'hello');
        $email->set('assignmentid', $secondassign->get_instance()->id);
        $email->save();

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_ASSIGNMENT_SUBMITTED);
        $plugin->set_config('judgementstartdate', time() - DAYSECS);

        $this->add_submission($student, $secondassign);

        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, time());
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages); // Not submitted yet so not in pool of judges.

        $this->submit_for_grading($student, $secondassign);

        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, time());
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages); // Nothing to compare as only one submission.

        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }
        $students[5] = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->add_submission($students[5], $secondassign); // Don't submit.

        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, time());
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(5, $messages); // Now everyone gets one except the user that added but did not submit.

        $editingteacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $plugin->set_config('judges', $editingteacherroleid);

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, time());
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages); // Now just the teacher.

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(time(), time() + 10);
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages); // All done.
    }

    public function test_emails_submitted_cutoffdate() {
        global $DB;

        $now = time();

        list($teacher, $editingteacher, $student, $secondassign, $plugin, $course) =
                $this->setupstandardscenario(['cutoffdate' => $now + 3]);
        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');
        $email = new judgerequestemail();
        $email->set('delay', 1);
        $email->set('subject', 'hello');
        $email->set('body', 'hello');
        $email->set('assignmentid', $secondassign->get_instance()->id);
        $email->save();

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);

        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }

        $students[5] = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->add_submission($students[5], $secondassign); // Don't submit.

        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, $now); // Before cut off.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages);

        while (time() < $now + 4) { // Wait until cut off as some core methods use time().
            sleep(1);
        }

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, $now + 4); // After cut off and delay.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(6, $messages); // Everyone as it's now too late for the unsubmitted user.
    }

    public function test_emails_submitted_cutoffdate_override() {
        global $DB;

        $now = time();

        list($teacher, $editingteacher, $student, $secondassign, $plugin, $course) =
                $this->setupstandardscenario(['cutoffdate' => $now + 10]);
        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');
        $email = new judgerequestemail();
        $email->set('delay', 1);
        $email->set('subject', 'hello');
        $email->set('body', 'hello');
        $email->set('assignmentid', $secondassign->get_instance()->id);
        $email->save();

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);
        $sink = $this->redirectEmails();

        for ($i = 0; $i < 4; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }

        $students[5] = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->add_submission($students[5], $secondassign); // Don't submit.

        $this->setUser($teacher);

        $override = (object) [
                'assignid'                 => $secondassign->get_instance()->id,
                'groupid'                  => 0,
                'userid'                   => $students[5]->id,
                'sortorder'                => 1,
                'allowsubmissionsfromdate' => 100,
                'duedate'                  => 200,
                'cutoffdate'               => $now + 100
        ];
        $DB->insert_record('assign_overrides', $override);

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, $now + 12); // After cut off and delay.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(4, $messages); // Everyone but the user with the extension.

        $sink->clear();
        ob_start();
        $this->submit_for_grading($students[5], $secondassign);
        $sink = $this->redirectMessages();
        $task->get_emails_to_send($now + 12, $now + 112); // After cut off and extension.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages); // Just the user with the extension.

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send($now + 113, $now + 150); // After cut off and extension.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages); // No one.
    }

    public function test_emails_submitted_cutoffdate_override_group() {
        global $DB;

        $now = time();

        list($teacher, $editingteacher, $student, $secondassign, $plugin, $course) =
                $this->setupstandardscenario(['cutoffdate' => $now + 10, 'teamsubmission' => 1]);
        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');
        $email = new judgerequestemail();
        $email->set('delay', 1);
        $email->set('subject', 'hello');
        $email->set('body', 'hello');
        $email->set('assignmentid', $secondassign->get_instance()->id);
        $email->save();

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);

        for ($j = 0; $j < 3; $j++) {
            $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
            $groupstudents = [];
            for ($i = 0; $i < 3; $i++) {
                $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
                groups_add_member($group, $student);
                $students[$student->id] = $student;
                $groupstudents[] = $student->id;
            }
            $groups[$group->id] = $groupstudents;
        }

        foreach ($groups as $groupid => $groupstudents) {
            $firststudent = $students[$groupstudents[0]];
            $this->add_submission($firststudent, $secondassign);
            foreach ($groupstudents as $studentid) {
                $this->submit_for_grading($students[$studentid], $secondassign);
                $students[$studentid] = $students[$studentid];
            }
            $groupsubmissions[$groupid] = $secondassign->get_group_submission($firststudent->id, 0, false)->id;
        }

        $this->setUser($teacher);

        $override = (object) [
                'assignid'                 => $secondassign->get_instance()->id,
                'groupid'                  => $groupid,
                'userid'                   => 0,
                'sortorder'                => 1,
                'allowsubmissionsfromdate' => 100,
                'duedate'                  => 200,
                'cutoffdate'               => $now + 100
        ];
        $DB->insert_record('assign_overrides', $override);

        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, $now + 12); // After cut off and delay.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(6, $messages); // Everyone but the users in the group with the extension.

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send($now + 12, $now + 112); // After cut off and extension.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(3, $messages); // Just the user with the extension.

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send($now + 113, $now + 150); // After cut off and extension.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages); // No one.
    }

    public function test_emails_submitted_cutoffdate_extension() {
        $now = time();

        list($teacher, $editingteacher, $student, $secondassign, $plugin, $course) =
                $this->setupstandardscenario(['duedate' => $now + 1, 'cutoffdate' => $now + 2]);
        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');
        $email = new judgerequestemail();
        $email->set('delay', 1);
        $email->set('subject', 'hello');
        $email->set('body', 'hello');
        $email->set('assignmentid', $secondassign->get_instance()->id);
        $email->save();

        $plugin->set_config('judges', \assign_submission_comparativejudgement::FAKEROLE_GRADABLE_USERS);
        $sink = $this->redirectEmails();

        for ($i = 0; $i < 5; $i++) {
            $students[$i] = $this->getDataGenerator()->create_and_enrol($course, 'student');
            $this->add_submission($students[$i], $secondassign);
            $this->submit_for_grading($students[$i], $secondassign);
        }

        $students[5] = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->add_submission($students[5], $secondassign); // Don't submit.

        $this->setUser($teacher);

        $secondassign->testable_save_user_extension($student->id, $now + 5);

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send(0, $now + 3); // After cut off and delay.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        // Students in the array and the one created at the end without submitting.
        $this->assertCount(6, $messages); // Everyone except the user with the extension.

        $sink->clear();
        ob_start();
        $this->submit_for_grading($students[5], $secondassign);

        while (time() < $now + 6) { // Wait until after the extension as some for methods use time().
            sleep(1);
        }

        $sink = $this->redirectMessages();
        $task->get_emails_to_send($now + 3, $now + 6); // After cut off and extension.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages); // Just the user with the extension.

        $sink->clear();
        ob_start();
        $sink = $this->redirectMessages();
        $task->get_emails_to_send($now + 7, $now + 10); // After cut off and extension.
        $output = ob_get_contents();
        ob_end_clean();

        $messages = $sink->get_messages();
        $this->assertCount(0, $messages); // No one.
    }

    public function test_email_body() {
        $now = time();

        list($teacher, $editingteacher, $student, $secondassign, $plugin, $course) =
                $this->setupstandardscenario(['duedate' => $now + 1, 'cutoffdate' => $now + 2]);

        $task = \core\task\manager::get_scheduled_task('assignsubmission_comparativejudgement\task\judgerequestemails');
        $email = new judgerequestemail();
        $email->set('delay', 1);
        $email->set('subject', 'hello');
        $email->set('body', '|[firstname]|[lastname]|[fullname]|[assignurl]|[judgeurl]|[assignname]|');
        $email->set('assignmentid', $secondassign->get_instance()->id);
        $email->save();

        $body = $task->get_message_body($student, $email->to_record(), $secondassign);

        $this->assertContains(fullname($student), $body);
    }

    /**
     * @return array
     */
    private function setupstandardscenario($assignops = []): array {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        // Assignment with submissions.
        $arr = [
                'name'                                                          => 'Assignment with submissions',
                'duedate'                                                       => time(),
                'attemptreopenmethod'                                           => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts'                                                   => 3,
                'submissiondrafts'                                              => 1,
                'assignsubmission_onlinetext_enabled'                           => 1,
                'assignsubmission_comparativejudgement_enabled'                 => 1,
                'assignsubmission_comparativejudgement_judgementswhileeditable' => false,
        ];
        $arr = array_merge($arr, $assignops);
        $secondassign = $this->create_instance($course, $arr);
        $plugin = \assign_submission_comparativejudgement::getplugin($secondassign);
        return array($teacher, $editingteacher, $student, $secondassign, $plugin, $course);
    }
}
