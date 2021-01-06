# What is comparative judgement?
Comparative judgement is a holistic approach to educational assessment that involves no rubrics, no marking and no assessor training. Instead, assessors are presented with two pieces of student work and asked simply to decide which is *better* in terms of a high-level criterion such as *problem solving* or *quality of writing*. Many pairwise comparisons from a group of assessors are then fitted to a statistical model to produce a score for each piece of work.

This plug-in enables you to use comparative judgement within Moodle for assessment and learning activities across all subjects.

### Assessing complex, diverse work.
Comparative judgement is particularly useful for *reliably assessing complex and diverse pieces of student work* that do not lend themselves to being captured in a rubric. Such work might include creative writing, performance pieces and student design projects. Research has shown that assessors are consistent at making pairwise comparisons of such student work. This consistency enables us to produce reliable and valid scores where traditioanl rubric-based marking methods often fail. There is a corpus of published research that used comparative judgement in higher education contexts, for example in [mathematics](http://dx.doi.org/10.1080/03075079.2013.821974) and [writing](http://dx.doi.org/10.1080/03075079.2013.821974). 

### Peer assessment.
The lack of need for assessor training means that comparative judgement is also well suited to *peer assessment*, and this Moodle plug-in has been designed with peer assessment in mind. Students upload their own work through the Moodle's **Assignment** activity, and can then make comparisons of their peers work. Importantly, students never see their own submission when making comparisons of peers' work. Moreover the plug-in supports anonymisation so that students do not know whose work they are comparing. For more examples of research exploring the use of comparative judgement in mathematics education, in a variety of contexts including with peer assessment, see [Ian Jones's webpage](https://iansajones.wordpress.com). 

### Flexible assessment and learning activities.
Comparative judgement is a very simple idea at heart: judges compare pairs of work and scores are generated. This simplicity enables great flexibility. It can be used for the summative assessment of complex and diverse pieces of student work, but it can also be used for learning activities and [research suggests](https://doi.org/10.1016/j.cogpsych.2012.03.003) the comparison of examples can be beneficial for learning. 

# Implementation of comparative judgement in the plug-in.
Decisions have to be made when designing comparative judgement engines, in for this plug-in these were as follows.

### Measures of reliability.
Reliability here refers to the extent that the same scores would have been produced by a comparative judgement exercise had an independent group of judges completed the comparisons instead. The plug-in reports the widely used Scale Separation Reliability (SSR) when the **Do comparisons** button is clicked. SSR is considered similar to Cronbach's alpha and a reliability value greater than **0.7** means you have acceptably reliable scores.

An important principle of comparative judgement is that the greater the number of comparisons, the greater the reliability. Therefore standard practice is to ensure there are enough comparisons to make sure the reliability is greater than **0.7**. To achieve acceptable reliability, 10 comparisons per submission or more is recommended in the [research literature](https://doi.org/10.1080/0969594X.2019.1602027). 

### Psuedorandom selection algorithm.
Comparative judgement involves presenting many pairs of submissions to judges. Typically we do not present all possible pairings between submissions because this woudl require an impractical number of comparisons to be collected. Therefore an algorithm is required to select which pairings of scripts to present. Earlier implementations of comparative judgement tended to use [adaptive algorithms](https://doi.org/10.1080/0969594X.2012.665354) which sought to maximise the efficiency of the process, and therefore minimise the number of comparisons required, by selecting pairings based on the judgements made so far. However, research has shown that adaptive algorithms tend to [artifically inflate the reliability](https://doi.org/10.1080/0969594X.2017.1418734) of comparative judgement assessment outcomes and so have gone out of fashion. 

Consequently the plug-in selects pairs of scripts at random to present to judges, although in practice the algorithm is pseudorandom as it attempts to choose pairings that conform to the following constraints.
* All submissions receive the same, or about the same, number of comparisons by the end of a comparative judgement exercise. 
* Submissions are viewed by a range of different judges.
* No judge sees their own submission.

### Peer assessment support.
As discussed above the plug-in is designed to support peer assessment. The specific design decisions made to support peer assessment, in contrast to other implementations of comparative judgement, are as follows.
* Students submit their own work via Moodle's **Assignment** activity.
* Submissions can (and should) be anonymous, so that students' do not know whose work they are comparing. To ensure anonymity, students must not write their name or ID numbers on their submissions. 
* Students never see their own script when they **Do comparisons**.

# Using comparative judgement for research.
This plug-in has been developed specifically with peer assessment in higher education contexts in mind. However as discussed above comparative judgement is a simple and infinitely flexible tool, and it is perfectly possible to use it for research studies that use a comparative judgement methodology. However, in general it is advisable to use the alternative [online platform](https://www.nomoremarking.com) provided by No More Marking Ltd. No More Marking is free to use for researchers, and [user guide is available](https://www.notion.so/nmm/No-More-Marking-for-researchers-70cb4eec46d547cd91c65ff2066d415f).

# Support
This plug-in was funded by the [Centre for Mathematical Cognition](https://www.lboro.ac.uk/research/cmc/) at [Loughborough University](https://www.lboro.ac.uk), and developed by [Andrew Hancox](https://uk.linkedin.com/in/andrewdchancox) and [Ian Jones](https://www.lboro.ac.uk/departments/mec/staff/ian-jones/) with input from [Chris Sangwin](https://www.maths.ed.ac.uk/~csangwin/). 

If you are interested in using comparative judgement for assessment, learning or research purposes please contact [Ian Jones](https://www.lboro.ac.uk/departments/mec/staff/ian-jones/) who is dead keen on getting people to use comparative judgement and who will be very pleased to hear from you.
