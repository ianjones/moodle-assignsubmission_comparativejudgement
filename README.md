# Comparative Judgement

This module extends the moodle assignment to provide support for comparative judgement (CJ).  Comparative judgement is an approach to assessment that offers an alternative to traditional marking, and this module has been specifically designed to support peer assessment and feedback. Students submit their work and then are presented with pairs of their peers' work and are asked simply to decide which is ‘better’ in terms of a high-level criterion such as 'problem solving' or ‘understanding of trigonometry’ or ‘quality of writing’. Many such pairwise decisions from the cohort are then used to produce a ranking of the submissions from which the module generates a score for each submission. 

Documentation is available.

1. [What is comparative judgement](docs/What_is_CJ.md) (CJ) and why would you use it?
2. [How to use this particular CJ implementation](docs/Using_the_CJ_plugin.md) as part of the Moodle assignment module.
3. [Advice on practical pedagogy](docs/Pedagogy_matters.md], including using CJ as a research tool.

Notes for [developers](docs/Developer.md) are also provided.

## Acknowledgements

This plug-in was funded by the [Centre for Mathematical Cognition](https://www.lboro.ac.uk/research/cmc/) at [Loughborough University](https://www.lboro.ac.uk), and developed by [Andrew Hancox](https://uk.linkedin.com/in/andrewdchancox) and [Ian Jones](https://www.lboro.ac.uk/departments/mec/staff/ian-jones/) with input from [Chris Sangwin](https://www.maths.ed.ac.uk/~csangwin/). 

If you are interested in using comparative judgement for assessment, learning or research purposes please contact [Ian Jones](https://www.lboro.ac.uk/departments/mec/staff/ian-jones/) who is dead keen on getting people to use comparative judgement and who will be very pleased to hear from you.

## Install guide

You need the following three components.

1. Clone this module into `mod/assign/submission/comparativejudgement`
2. Clone the [rhandler](https://github.com/andrewhancox/local_rhandler)  into `local/rhandler`
3. Make sure the command line `R` is available on your server.  Instructions for Ubuntu 18 are below.

### Install R 

#### OS X with Homebrew
Install xcode tools:

`xcode-select --install`

Download the latest version of R from (R-4.4.2-arm64.pkg)
` https://cran.r-project.org/bin/macosx/`

Install the dependencies:

`cd MOODLEROOT/mod/assign/submission/comparativejudgement/lib`

`sudo Rscript prereqs.R`


#### Ubuntu 18

`sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys E298A3A825C0D65DFD57CBB651716619E084DAB9`

`sudo add-apt-repository 'deb https://cloud.r-project.org/bin/linux/ubuntu bionic-cran40/'`

`sudo apt update`

`sudo apt install r-base`


#### Install R dependencies

If running remotely, first copy over the R scripts:

Create the folder structure

`ssh _username_@_servername_ "mkdir -p ~/mod/assign/submission/comparativejudgement/lib"`

Copy the files

`scp *.R _username_@_servername_:~/mod/assign/submission/comparativejudgement/lib`

Then (local or remote) run the prereqs script to install required libraries into your R environment:

`sudo Rscript prereqs.R`

### Test R

Local version:

`cat docs/exampledecisions.csv | Rscript lib/pipeablescript.R > docs/oput.csv`

Remote version:

`cat docs/exampledecisions.csv |  su - WEBSERVERUSERCONTEXT -c 'ssh _username_@_servername_ "Rscript pipeablescript.R"'`


https://rtask.thinkr.fr/installation-of-r-3-5-on-ubuntu-18-04-lts-and-tips-for-spatial-packages/
