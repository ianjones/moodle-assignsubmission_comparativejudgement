# Setting up a comparative judgement assignment.

In this getting started guide we will add a comparative judgement assignment to a Moodle page, and work through the settings.

## 1. Add an Assignment. 
Click on **Add an activity or resource** and select **Assignment**.
![addAssignment](https://user-images.githubusercontent.com/651129/103677610-68ee8a80-4f7a-11eb-8283-7d93b37ee168.png)

## 2. Settings: Submission types.
Only settings specific to the comparative judgement plug-in are detailed below. These settings are all found in **Submission types**.

* Select **Comparative judgement**. You can decide whether students will upload **File submissions** or submit **Online text** or both.
* You can specify the number of comparisons each judge will complete using **Minimum judgements per user** and **Maximum judgements per user**. For a summative assessment these numbers might be the same, perhaps 10 comparisons per user. For learning activities you might require a minimum of 10 comparisons per user, but no practical upper limit so set it to 999.
* For **Minimum judgements per submission** a value of 10 or more is recommended in the [research literature](https://doi.org/10.1080/0969594X.2019.1602027). 
* To allow judges to make written comments on submissions when doing comparisons, **Enable comparison comments**.
* You can set a **Start date for judgements**, or not to allow comparisons to start immediately.
* For a summative assessment you would likely not select **Allow user to judge while their submission is editable**. For learning activities you might select this.
* Enter the assessment criteria that is the basis of making comparisons in **Introduction for judges**. This is likely to be something like *Choose the better essay* or *Who is the better mathematician?* and so on. 
* Select who you want to act as **Judges**. This would usually be the students and the teaching staff.
* Set the **Maximum number of uploaded files** to 1. 
* It is best to restrict the **Maximum submission size** to 10MB or less. Remember that when judges do comparisons they have to allow two submissions to download for every comparison. The larger the submissions, the slower and more data-heavy the comparison process.

## 3. Manage exemplars.
You can upload exemplar submissions that will be included when judges do comparisons. These might be examples of high-quality submissions that you wish your students to be exposed to when doing judging. They might be submissions that represent grade boundaries that you later use to assign grades to students work, and to ensure standards are maintained across cohorts. 

## 4. Manage judges.
You can monitor the quantity and quality of the comparisons completed by each judge. The column headings are as follows.
* **Judge ID**: a unique identifier assigned by the plug-in to help connect judges with their comparisons and their submissions. See LINK NEEDED.
* **Comparisons made**: the number of comparisons made.
* **Time spent**: the total time spent doing comparisons. 
* **Average time spent**: the median time taken for each comparison. If this is very low perhaps the judge is not taking their comparisons seriously.
* **Min./Max. time spent**: further information on the time taken for each comparison.
* **Ratio of sides chosen**: the number of LEFT clicks and the number of RIGHT clicks made. This helps monitor the quality of the judge's comparisons. If they only every click LEFT then perhaps they are not taking their comparisons seriously.
* **First/Last comparison**: the date and time of the first comparison and the most recent comparison made by the judge.
* **Include in grading**: exclude a judge's comparisons from contributing to submission scores, for example if the judge seems not to have taken their comparisons seriously.

## 5. Manage submissions.
You can mointor both your uploaded exemplars and the students' submissions. You can also **Calculate grades** and download **Raw judgement data**.

### Column headings
* **Submission ID**: a unique identifier assigned by the plug-in to help connect judges with their comparisons and their submissions. See LINK NEEDED.
* **Comparisons received**: the number of comparisons received by the exemplar or submission.
* **Time spent**: the total time judges have collectively spent doing comparisons on the exemplar or submission. 
* **Average time spent**: the median time judges have collectively spent doing comparisons on the exemplar or submission.
* **First/Last comparison**: the date and time of the first comparison and the most recent comparison of the exemplar or submission.
* **Wins/Losses**: the number of times the exemplar or submission was selected (**Win**), and number of times the other exemplar or submission was selected (**Loss**). 
* **Score**: the score (see **Calculate grades** below), on a scale of 0 to 100, received by the exemplar or submission. 
* **Include in grading**: exclude an exemplar or submission receiving a score.

### Grades and judgement data
At the bottom of the page are three buttons, as follows.
* **Calculate grades**: uses the comparisons made so far to calculate a score for each exemplar or submission. 
* **Raw judgement data**: for advance users, download the comparisons made and calculate your own scores offline. See LINK NEEDED.
* **Copy grades to gradebook**: click once all comparisons are complete to copy the grades to the gradebook.

## 6. Manage judgement request emails.
Use this feature to send emails informaing or reminding judges to start or complete their comparisons.

## 7. Do comparison.
This button becomes available to judges once a comparative judgement assignment has been set up and there are at least 2 exemplars or submissions uploaded, and the number of comparisons required (see **Settings** above) is greater than 0.
