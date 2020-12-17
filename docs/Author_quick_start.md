# Comparative judgement

Comparative judgement (CJ) is a holistic approach to educational assessment that involves no rubrics, no marking and no assessor training. Instead, assessors are presented with two pieces of student work and asked simply to decide which is ‘better’ in terms of a high-level criterion such as ‘problem solving’ or ‘quality of writing’. Many pairwise comparisons from a group of assessors are then fitted to a statistical model to produce a score for each piece of work.

Comparative judgement is particularly useful for reliably assessing complex and varied pieces of student work that do not lend themselves to being captured in a rubric. Such work might include creative writing, performance pieces and student design projects. Research has shown that assessors are consistent at making pairwise comparisons of such student work according to a high level criterion such as “quality of writing”.

Comparative judgement is particularly well suited to peer assessment, and this Moodle plug-in has been designed with peer assessment in mind. Students upload their own work through the Assessment resource, and can then make comparisons of their peers work. 

# Setting comparative judgement tasks in Moodle

This project extends the assessment capabilities of the Moodle Assignment module to provide teachers with the option to create a "comparative judgement" task.

Comparative judgement has three phases.

1. Students complete an Assignment task.
2. Judges make pairwise comparisons between students' submissions.
3. The pairwise judgements are used to create a rank order of submissions.

The rank order can then be used to generate a numerical score.

It is possible, but not necessary, for the students to also be judges.  Or, two separate groups of people can (1) make submissions, and (2) complete judgements.  The plug-in is designed so that no student is presented with their own piece of work.

Since this project extends the the Moodle Assignment module, the code can be found under `mod/assign/submission/comparativejudgement` on the Moodle site.  That is, it is merely another `submission` type for the Assignment.

## 1. Setting up an assignment task with CJ

This project extends the assessment capabilities of the Moodle Assignment module.  Hence, the first task is to create a Moodle Assignment task in the normal way. This includes any submission instructions, due dates etc.

Under "Submission types" choose "Comparative judgement".  This will reveal a number of options you should select to set up the judgement options.

All the judgement options specifically related to CJ are set in the "Submission types" section of the Assignment.  Some of the settings in the "Submission types" section of the Assignment are not related to CJ, e.g. whether students type an answer or upload a file.

## 2. Making judgements

Users will be automatically invited to make judgements when they choose the Assignment.  In practice, judges may need to be ecouraged to particpate in this second phase of the CJ process!

## 3. Generating the rank order and marks.

Once sufficient judgements have been made, as teacher, navigate to the Assignment context, choose “Manage submissions” and click the “Calculate grades” button at the bottom of this page.

Grades are calculated by fitting the pairwise decision data to a statistical model. When you click “Calculate grades” at the bottom of the “Manage submissions” page, each submission is also assigned a score on a scale of 0 to 100 based on this model. These grades can be viewed in the “Manage submissions” page and there is also a button to so that they can be “Copied to gradebook”.

When you click “Calculate grades” button you also get a reliability estimate, that appears just above the button. This number is on a scale from 0 to 1, and if it is below 0.7 then you should collect more judgements.

Teachers can choose to copy the calcuated grades into the Moodle gradbook, or only some of them.  The grades can be edited by hand later.

