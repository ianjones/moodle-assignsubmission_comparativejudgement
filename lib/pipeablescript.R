#clear all variables
rm(list = ls())


#get the packages we need
require(dplyr)
library(sirt)
require(readr)

# Dump all output to stderr as we don't want it yet
sink(stderr(), type = "output")

#scores scale [could allow admin in plugin to set these?]
SD <- 15
m <- 65 #mean
min_score <- 0
max_score <- 100
dp <- 0 #number of decimal places


#get the decision data from the Moodle PlugIn
#assume columns (1) submissionid (2) Won (3) Lost 
decisions <- read.csv(file("stdin"), header=T, stringsAsFactors=F) #this line will read the data passed from PHP
#Create a unique factor level for each submission 
players <- unique(c(decisions$Won,decisions$Lost))
#Fit decisions to Bradley-Terry Model to get a score for each candidate
df <- data.frame(id1=decisions$Won, id2=decisions$Lost, result=1)
df$id1 <- factor(df$id1,levels=players)
df$id2 <- factor(df$id2,levels=players)
mod1 <- sirt::btm(df ,  maxit=400 , fix.eta=0 , ignore.ties=TRUE )


#Generate main output variables
#First the scores for each submission with columns "submissionid","Score")
scores <- mod1$effects[,c(1,9)]
colnames(scores) <- c("submissionid","Score")
#scale the scores and round them
scores$Score <- round(scale(scores[,2])*SD+m,dp)
#top and tail
scores$Score[ scores$Score<min_score ] <- min_score
scores$Score[ scores$Score>max_score ] <- max_score
#Next the reliability
scores$Reliability <- round(mod1$mle.rel,2)



# Stop dumping output to stderr, we want it going to stdout
sink()

#output as csv
scores$Score <- as.vector(scores$Score) #format scores to stop format_csv crashing
cat(format_csv(scores))
