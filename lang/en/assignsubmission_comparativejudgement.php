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

$string['addexemplar'] = 'Add exemplar';
$string['alwayssameside'] = 'Judge always picked the same side';
$string['avgtimetakencomparing'] = 'Median time spent';
$string['body'] = 'Body';
$string['body_help'] = 'Body of message to send to judges, available placeholders are:
       [firstname]
       [lastname]
       [fullname]
       [assignurl]
       [judgeurl]
       [assignname]';
$string['bodydefault'] = '[fullname],
As part of the grading of assignment [assignname] you are required to compare other submissions, you can do this by going to [judgeurl].';
$string['calculategrades'] = 'Calculate scores';
$string['calculategradesfirst'] = 'Please calculate scores before copying grades to the gradebook.';
$string['chooseleft'] = 'Choose Left';
$string['chooseright'] = 'Choose Right';
$string['comment'] = 'Comment';
$string['commentpublished'] = 'Published';
$string['comments'] = 'Comments';
$string['commentsimported'] = 'Comments imported';
$string['comparativejudgement:manageemails'] = 'Manage emails';
$string['comparativejudgement:manageexemplars'] = 'Manage submissions';
$string['comparativejudgement_allowcompareexemplars'] = 'Allow comparison of pairs of exemplars';
$string['comparativejudgement_allowcompareexemplars_help'] = 'If this setting is disabled then judges will never see pairs of exemplars for comparison';
$string['comparativejudgement_allowrepeatcomparisons'] = 'Enable repeat comparisons';
$string['comparativejudgement_allowrepeatcomparisons_help'] = 'If this setting is disabled then judges will stop being presented with new pairs once they have compared every submission to every other submission once.';
$string['comparativejudgement_enablecomments'] = 'Enable comparison comments';
$string['comparativejudgement_introduction'] = 'Introduction for judges';
$string['comparativejudgement_judgementswhileeditable'] = 'Allow judge to make comparisons while their submission is editable';
$string['comparedsubmission'] = 'Compared submission';
$string['comparisondone'] = 'Comparison done';
$string['comparisonprogress'] = 'Comaprison {$a->number} of {$a->required}';
$string['copytogradebook'] = 'Copy grades to gradebook';
$string['delay'] = 'Delay';
$string['delaydetail'] = '{$a->delay} (currently {$a->current})';
$string['delaydetailnever'] = 'never';
$string['deletecomparison'] = 'Delete comparison';
$string['deleteemail'] = 'Delete email';
$string['deleteexemplar'] = 'Delete exemplar';
$string['docomparison'] = 'Do comparison';
$string['downloadrawjudgedata'] = 'Raw comparison data';
$string['editemail'] = 'Edit email';
$string['editexemplar'] = 'Edit exemplar';
$string['enabled'] = 'Comparative judgement';
$string['enabled_help'] = 'If enabled, submissions are graded through comparative judgement.';
$string['errorexecutingscript'] = 'Error executing RScript';
$string['event:commentsimported'] = 'Comparison comments imported';
$string['event:comparisondeleted'] = 'Comparison deleted';
$string['event:comparisonmade'] = 'Comparison made';
$string['event:gradescalculated'] = 'Grades calculated';
$string['event:gradesimported'] = 'Grades imported';
$string['event:judgerequestemail_deleted'] = 'Judge request email deleted';
$string['event:judgerequestemail_modified'] = 'Judge request email modified';
$string['event:judgerequestemail_sent'] = 'Judge request email sent';
$string['exemplartitle'] = 'Title';
$string['fakerole_assignment_submitted'] = 'Users with completed submissions';
$string['fakerole_gradable_users'] = 'Gradable users';
$string['firstcomparison'] = 'First comparison';
$string['gradescopied'] = 'Grades copied to gradebook';
$string['importcomments'] = 'Import comments';
$string['include'] = 'Include in grading';
$string['judge'] = 'Judge';
$string['judgeid'] = 'Judge id';
$string['judgementstartdate'] = 'Start date for comparisons';
$string['judges'] = 'Judges';
$string['lastcalculation'] = 'Last calculation of grades at: {$a}';
$string['lastcomparison'] = 'Last comparison';
$string['lastreliability'] = 'Last reliability: {$a}';
$string['left'] = 'Left';
$string['losingsubmission'] = 'Losing submission';
$string['losingsubmissionid'] = 'Losing submission';
$string['losses'] = 'Losses';
$string['managecomparisoncomments'] = 'Comparison comments';
$string['managecomparisons'] = 'Manage comparisons';
$string['managecomparisonswithcount'] = 'Manage comparisons ({$a})';
$string['manageexemplars'] = 'Manage exemplars';
$string['manageexemplarswithcount'] = 'Manage exemplars ({$a})';
$string['managejudgerequestemail'] = 'Manage comparison request emails';
$string['managejudgerequestemailintro'] =
        'Reminder emails will be sent to all elligible judges, they will be sent out on a schedule starting from the comparison start date if set, otherwise the submission cut off date or due date. If cut off date or due date are used then any extensions applied to users will impact on the date they receive the reminder.';
$string['managejudges'] = 'Manage judges';
$string['managejudgeswithcount'] = 'Manage judges ({$a})';
$string['managesubmissions'] = 'Manage submissions';
$string['managesubmissionswithcount'] = 'Manage submissions ({$a})';
$string['maxjudgementsperuser'] = 'Maximum comparisons per judge';
$string['maxtimetakencomparing'] = 'Max. time spent';
$string['minjudgementspersubmission'] = 'Minimum comparisons per submission';
$string['minjudgementsperuser'] = 'Minimum comparisons per judge';
$string['mintimetakencomparing'] = 'Min. time spent';
$string['never'] = 'Never';
$string['newreminderemail'] = 'New reminder email';
$string['noofcomparisons'] = 'Comparisons made';
$string['noofcomparisonsreceived'] = 'Comparisons received';
$string['nothingtocompare'] = 'No comparisons to compare';
$string['pluginname'] = 'Comparative judgement';
$string['privacy:judgement'] = 'Comparison received';
$string['privacy:judgementmade'] = 'Comparison made';
$string['privacy:metadata:assignmentid'] = 'Assignment ID';
$string['privacy:metadata:assignsubmission_comparativejudgement:assignmentid'] = 'Assignment module id';
$string['privacy:metadata:assignsubmission_comparativejudgement:assignsubmission_comp:tablepurpose'] = 'Comparisons performed by judges';
$string['privacy:metadata:assignsubmission_comparativejudgement:assignsubmission_compsubs:tablepurpose'] = 'Assignment submissions that have been compared and related comments';
$string['privacy:metadata:assignsubmission_comparativejudgement:assignsubmission_rankingsub:tablepurpose'] = 'Scores that have been automatically generated for a submission';
$string['privacy:metadata:assignsubmission_comparativejudgement:comments'] = 'Comparison comments';
$string['privacy:metadata:assignsubmission_comparativejudgement:judgementid'] = 'Related comparisonid';
$string['privacy:metadata:assignsubmission_comparativejudgement:rankingid'] = 'Related ranking id';
$string['privacy:metadata:assignsubmission_comparativejudgement:score'] = 'Auto-generated score for assignment submission';
$string['privacy:metadata:assignsubmission_comparativejudgement:submissionid'] = 'Related submissionid';
$string['privacy:metadata:assignsubmission_comparativejudgement:timetaken'] = 'Time taken';
$string['privacy:metadata:assignsubmission_comparativejudgement:usermodified'] = 'Judge who modified the record';
$string['privacy:metadata:assignsubmission_comparativejudgement:winningsubmission'] = 'Winning submission chosen during comparison';
$string['privacy:metadata:assignsubmission_comparativejudgement:winningsubmissionposition'] = 'Position of winning submission chosen during comparison';
$string['privacy:metadata:submissionpurpose'] = 'The submission ID that links to submissions for the user.';
$string['privacy:ranking'] = 'Ranking';
$string['remainingjudgements'] = 'Minimum comparisons remaining:';
$string['right'] = 'Right';
$string['savechangesandaddanother'] = 'Save changes and add another';
$string['score'] = 'Score';
$string['sendjudgerequestemails'] = 'Send comparison request emails';
$string['sidespicked'] = 'Ratio of sides chosen';
$string['stopjudging'] = 'Finish judging';
$string['subject'] = 'Subject';
$string['subjectdefault'] = 'subjectdefault';
$string['submission'] = 'Submission';
$string['submissionid'] = 'Submission id';
$string['timetaken'] = 'Time spent';
$string['timetakencomparing'] = 'Time spent';
$string['userupload'] = 'Uploaded submission';
$string['viewassignment'] = 'View';
$string['viewexemplar'] = 'View';
$string['winningposition'] = 'Winning position';
$string['winningsubmission'] = 'Winning submission';
$string['winningsubmissionid'] = 'Winning submission';
$string['wins'] = 'Wins';
