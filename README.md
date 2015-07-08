# Local activity bookmark

A Moodle local plugin that redirects a user to the activity that they most recently visited within a course.

## Dependencies

Moodle 2.9

## Installation

Relative to the directory in which Moodle is installed:

    cd local
    git clone https://github.com/INTO-University-Partnerships/local-activity-bookmark activity_bookmark
    cd ..
    php admin/cli/upgrade.php
