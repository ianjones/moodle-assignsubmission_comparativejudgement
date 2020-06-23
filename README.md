# Comparative Judgement Submissions
## Install guide
###Ubuntu 18
####Install R
`sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys E298A3A825C0D65DFD57CBB651716619E084DAB9`

`sudo add-apt-repository deb https://cloud.r-project.org/bin/linux/ubuntu bionic-cran40/`

`sudo apt update`

`sudo apt install r-base`


####Install R dependencies

If running remotely, first copy over the R scripts:

`scp *.R _username_@_servername_:~`

Run the prereqs script to install required libraries into your R environment:

`sudo Rscript prereqs.R`

####Test

Local version:

`cat docs/exampledecisions.csv | Rscript lib/pipeablescript.R > docs/oput.csv`

Remote version:

`cat docs/exampledecisions.csv | ssh _username_@_servername_ "Rscript pipeablescript.R"`



https://rtask.thinkr.fr/installation-of-r-3-5-on-ubuntu-18-04-lts-and-tips-for-spatial-packages/