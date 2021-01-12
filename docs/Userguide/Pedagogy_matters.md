# Pedagogy matters
Comparative judgement is a simple idea: compare pairs of submitted work and produce a score for each submission. This simplicity means that the comparative judgement Moodle plug-in is a very flexible tool that can be used in all sorts of ways. On this page we 
* cover the basics of using comparative judgement for **summative assessment**;
* provide an **example use case**, based on how one of the developers of the plug-in uses it;
* consider issues such as **how to criterion reference scores**, and how to provide **feedback to students**;
* summarise some published studies from across various subjects in reporting uses of **comparative judgemt in higher education**.

We hope the below inspires you to use comparative judgement in novel ways that are most helpful to you, as well as providing some ideas to get you started. 

# 1. Summative assessment.
The plug-in has been designed with summative assessment in mind. See the [guide on setting up a comparative judgement assignment](https://github.com/ianjones/moodle-assignsubmission_comparativejudgement/blob/master/docs/Userguide/Using_the_CJ_plugin.md) for more technical information. Here we will focus on pedagogic considerations.

There are three main parts to using the plug-in for summative assessment.

## i) Administer an assessment task.
Set the students an open-ended or performance-based task that you plan to assess. This might be a piece of extended writing, an e-Portfolio of a design project, a movie file of a presentation, and any other complex piece of work than can be submitted as a computer file (see **Section 5**, below, for further examples). Remember that comparative judgement is well-suited to assessing complicated pieces of work that vary greatly from student to student; if you have more restricted or standardised pieces of work to assess then comparative judgement might not be the best choice. 

## ii) Assess the task.
There are three parts to assessing the task. First, set up an **Assignment** activity in Moodle, as detailed [here](https://github.com/ianjones/moodle-assignsubmission_comparativejudgement/blob/master/docs/Userguide/Using_the_CJ_plugin.md).

Second, students submit their work via the **Assignment** link.

Third, students make pairwise comparisons of their peers' work (about 20 per student), and the teacher also contributes some pairwise comparisons for moderation purposes (about twice as many comparisons as the number of students on the module is recommended). Students are told that their final score will depend entirely on the quality of their work as moderated by the lecturer, but that their score will not be released to them unless they complete their comparisons in full.

## ii) Get the scores.
Once enough comparisons have been collected (a minimum of 10 comparisons per submission is recommended), the score for each submission can be calculated, as detailed [here](https://github.com/ianjones/moodle-assignsubmission_comparativejudgement/blob/master/docs/Userguide/Using_the_CJ_plugin.md). You are likely to want to ensure the scores are criterion referenced, and to know where to put the grade boundaries, before providing the scores to students. See **Section 3** below on how to do this. 

That's the bare bones, but in practice a motivation for using comparative judgement in higher education isn't just to assess complex, varied pieces of work for summative purposes. It is also to promote deep learning through engaging students with peer assessment and peer feedback activities, and this is described in **Section 4** below.

# 2. Example use case.
Comparative judgement has been used for several years on mathematics modules at Loughborough University. This section gives a high-level overview of typical usage, for the case of a Foundation Mathematics module. 

Early in the module, usually in the first lecture, students are first presented with an open-ended prompt, such as 

>*What is an equation? Give examples of how equations can be useful.* 

at the top of a blank page. Such open-ended tasks are usually unfamiliar to students in mathematics, and they are asked to answer as best they can using writing, diagrams and symbols. They are given about ten minutes to answer and following the lecture they submit their responses via an **Assignment** link in Moodle.

In the subsequent lecture, which might take place in a computer lab or using students' own devices, the students are instructed to complete at least 10 comparisons of their peers' (anonymised) submissions. The students are told that for each pairing they should, for the case of this example 

>*Choose the better student with the better understanding of equation.* 

Students are told to discuss and justify their decisions within one another whilst making comparisons, and they typically do this in groups of two or three.

Once the comparisons are complete the lecturer facilitates a whole class discussion in which students share what they think distinguishes better quality responses. During these discussions the lecturer might reveal the top two or three (anonymised) submissions to further stimulate discussion; lower-scoring submissions are not revealed, even anonymised, to spare the feelings of the students who scored poorly. For the case of open-ended prompts in mathematics similar conclusions usually arise from the group discussion: for example, that better quality responses use a range of representations (prose, equations, graphs, diagrams), tend not contain mathematical errors, contain worked through examples, and so on. 

The above activity is repeated three or four times over the duration of a module, with different open-ended prompts. As the summative test nears towards the end of the module the students are told that the best way to revise is to practise the questions, submit their responses, and to continue making comparisons of their peers' work (there is no limit on how many comparisons they can make and how many responses they can submit once an **Assignment** is set up on Moodle). It is common in revision sessions to see students choosing to revisit the comparative judgement activities in small groups to discuss and make notes on how to produce high-quality responses. 

# 3. How to criterion reference scores.
A common misconception about comparative judgement is that it is inherently norm-referenced. This is an understandable assumption given the scores are generated solely from relative comparisons of students' work. However, as is the case for scores produced by any assessment method, the scores can be interpreted and cut based on quotas or criterion. In practice most comparative judgement assessments are interpreted and the grade-boundaries applied using criterion-referencing, not norm-referencing.

There are various ways this can be done. One way is to examine the scores for natural breaks, and then scrutinise the submissions around the breaks to identify where the grade boundaries should go. The examiner can then produce a feedback report summarising the features of the submissions at each grade level. Such an approach has been [described here](https://doi.org/10.1080/0969594X.2014.978839).

Another approach - and this is used in the *use case* described in **Section 2** above - is to seed the **Assignment** task with grade boundary submissions. The **Manage exemplars** feature in the plug-in was designed primarily for this purpose and is [described here](https://github.com/ianjones/moodle-assignsubmission_comparativejudgement/blob/master/docs/Userguide/Using_the_CJ_plugin.md). The exemplars are either selected from moderated submissions by previous cohorts, or can be contrived by the lecturer. Importantly, students need not know when doing their comparison whether a pairing presented to them contains an exemplar so the process is seamless.

A variant on this method, perhaps more appropriate when lecturers rather than peers are doing comparisons, is to include exemplars that are not grade-boundary submissions but instead explicit statements of the required criteria. When doing comparisons, judges will mostly see pairs of submissions, but occasionally will see a submission paired against a grade descriptor: they then decide whether the submission is meets or fails to meet the descriptor. 

Another variant on this same method is to include overt grade boundaries rather than grade descriptors; for example this might be PDFs that with nothing written on them other than __*A/B*__, and __*B/C*__ and so on. In this case when a judge sees a submission paired against an explicit grade boundary they decide whether the submission is above or below the boundary. 

# 4. Feedback to students.
A common criticism of comparative judgement is that it provides no feedback to students. 

This criticism usually refers to the lack of individualised written comments on students' submissions following comparative judgement. To address this concern, the Moodle plug-in allows judges to make written comments on individual responses while doing comparisons for those users who want written feedback, or who work in institutions where assessment innovation is constrained to require written feedback. 

However, in practice when judges write comments it slows down and makes less enjoyable the comparison process. We instead advocate taking a different view on feedback, and making sure to communicate this different view to students, when using comparative judgement. Our view is that when students compare their peers' work, discuss their peers' work in small groups, engage in teacher-facilitated large group discussions, and reflect on which submissions come top in formative assessment tasks, then this constitutes a rich and high-quality form of feedback. 

In our experience students are receptive to peer learning based on comparative judgement, and can appreciate that feedback does not have to mean red ink on their work. (Whether the institution where you work can appreciate this might be another matter!)

# 5. Comparative judgement in higher education.
Below is a list of peer-reviewed academic outputs describing the use of comparative judgement in higher education across a range of disciplines.

## Mathematics.
* Jones, I., & Alcock, L. (2014). Peer assessment without assessment criteria. *Studies in Higher Education, 39*, 1774–1787. [Link.](https://doi.org/10.1080/03075079.2013.821974)
* Jones, I., & Sirl, D. (2017). Peer assessment of mathematical understanding using comparative judgement. *Nordic Studies in Mathematics Education, 22*, 147–164. [Link.](https://repository.lboro.ac.uk/articles/journal_contribution/Peer_assessment_of_mathematical_understanding_using_comparative_judgement/9378092)
* Davies, B., Alcock, L., & Jones, I. (2020). Comparative judgement, proof summaries and proof comprehension. *Educational Studies in Mathematics, 105*, 181–197. [Link.](https://doi.org/10.1007/s10649-020-09984-x)

## Writing.
* van Daal, T., Lesterhuis, M., Coertjens, L., Donche, V., & De Maeyer, S. (2019). Validity of comparative judgement to assess academic writing: Examining implications of its holistic character and building on a shared consensus. *Assessment in Education: Principles, Policy & Practice, 26*, 59–74. [Link.](https://doi.org/10.1080/0969594X.2016.1253542)

## Teacher training.
* Roose, I., Goossens, M., Vanderlinde, R., Vantieghem, W., & Avermaet, P. V. (2018). Measuring professional vision of inclusive classrooms in secondary education through video-based comparative judgement: An expert study. *Studies in Educational Evaluation, 56*, 71–84. [Link.](https://doi.org/10.1016/j.stueduc.2017.11.007)
* Seery, N., Canty, D., & Phelan, P. (2012). The validity and value of peer assessment using Adaptive Comparative Judgement in design driven practical education. *International Journal of Technology and Design Education, 22*, 205–226. [Link.](https://doi.org/10.1007/s10798-011-9194-0)

## Engineering.
* Williams, P. J. (2012). Investigating the feasibility of using digital representations of work for performance assessment in engineering. *International Journal of Technology and Design Education, 22*, 187–203. [Link.](https://doi.org/10.1007/s10798-011-9192-2)




