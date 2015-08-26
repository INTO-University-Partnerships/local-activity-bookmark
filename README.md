# Local activity bookmark

A Moodle local plugin that redirects a user to the activity that they most recently visited within a course.

Commands are relative to the directory in which Moodle is installed.

## Dependencies

Moodle 2.9

The following packages must be added to `composer.json`:

    "require": {
        "silex/silex": "1.3.*",
        "lstrojny/functional-php": "1.0.0"
    },
    "require-dev": {
        "mockery/mockery": "dev-master"
    }

## Installation

Install [Composer](https://getcomposer.org/download/) if it isn't already.

    ./composer.phar self-update
    ./composer.phar update
    cd local
    git clone https://github.com/INTO-University-Partnerships/local-activity-bookmark activity_bookmark
    cd ..
    php admin/cli/upgrade.php

## Apache rewrite rule

Add the following Apache rewrite rule:

    RewriteRule ^(/course/bookmark) /local/activity_bookmark/index.php?slug=$1 [QSA,L]

## Tests

### PHPUnit

    php admin/tool/phpunit/cli/util.php --buildcomponentconfigs
    vendor/bin/phpunit -c local/activity_bookmark
