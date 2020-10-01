# Comparative Judgement

This module extends the moodle assignment to provide support for comparative judgement (CJ).  Comparative judgement is an approach to assessment that offers an alternative to traditional marking, and this module has been specifically designed to support peer assessment and feedback. Students submit their work and then are presented with pairs of their peers' work and are asked simply to decide which is ‘better’ in terms of a high-level criterion such as 'problem solving' or ‘understanding of trigonometry’ or ‘quality of writing’. Many such pairwise decisions from the cohort are then used to produce a score for each submission. 

## Install guide

You need the following three components.

1. Clone this module into `mod/assign/submission/comparativejudgement`
2. Clone the [rhandler](https://github.com/andrewhancox/local_rhandler)  into `local/rhandler`
3. Make sure the command line `R` is available on your server.  Instructions for Ubuntu 18 are below.

### Install R 

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
