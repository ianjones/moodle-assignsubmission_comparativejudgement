# Comparative Judgement Submissions

This module extends the moodle assignment to provide support for comparative judgement (CJ).  Comparative judgement is an approach to assessment that offers an alternative to traditional marking. The underlying theoretical basis is a well-established psychological principle that people are more reliable when comparing one sense impression against another than they are at judging an impression in isolation.  E.g. see Thurstone (1927). For example, it is easier to decide which of two weights is the heavier than to estimate (to the nearest gram, say) a single weight in isolation.  The basic mechanics of CJ are simple. Experts are presented with pairs of students’ work and asked to decide which is "better" in terms of some global construct such as "mathematical ability". The experts’ decisions are fitted to a statistical model to produce a standardised parameter estimate for each student. The parameter estimates are then used to construct a scaled rank order of student work from "worst" to "best" and the usual assessment arrangements, such as allocating grades, can be applied to the rank order.

## Install guide

You need the following three components.

1. Clone this module into `mod/assign/submission/comparativejudgement`
2. Clone the [rhandler](https://github.com/andrewhancox/local_rhandler)  into `local/rhandler`
3. Make sure the command line `R` is available on your server.  Instructions for Ubuntu 18 are below.

### Install R 

#### Ubuntu 18

`sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys E298A3A825C0D65DFD57CBB651716619E084DAB9`

`sudo add-apt-repository deb https://cloud.r-project.org/bin/linux/ubuntu bionic-cran40/`

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
