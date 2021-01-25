# Developer notes

These notes are intended for developers of the plugin.

This project extends the the Moodle Assignment module.

The code is in `mod/assign/submission/comparativejudgement`.

The plugin also depends on [rhandler](https://github.com/andrewhancox/local_rhandler)  which needs to be cloned into `local/rhandler`.

## PHP Unit tests

Moodle uses PHPUnit for its unit tests. Setting this up and getting it working
is tricky, but instructions are provided in [the Moodle PHPUnit documentation](http://docs.moodle.org/dev/PHPUnit).

Once you have executed

    php admin/tool/phpunit/cli/init.php

Then execute

    vendor/bin/phpunit --group assignsubmission_comparativejudgement

To make sure this keeps working, please annotate all test classes with

    /**
     * @group assignsubmission_comparativejudgement
     */

## Code checker

To check the code style against the Moodle coding checker use

    mod/assign/submission/comparativejudgement

# Development history

## Version 1.0

Version 1.0 was released (beta) in September 2020.

This plug-in was funded by the [Centre for Mathematical Cognition](https://www.lboro.ac.uk/research/cmc/) at [Loughborough University](https://www.lboro.ac.uk), and developed by [Andrew Hancox](https://uk.linkedin.com/in/andrewdchancox) and [Ian Jones](https://www.lboro.ac.uk/departments/mec/staff/ian-jones/) with input from [Chris Sangwin](https://www.maths.ed.ac.uk/~csangwin/). 
    