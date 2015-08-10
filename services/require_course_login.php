<?php

defined('MOODLE_INTERNAL') || die();

$app['require_course_login'] = $app->protect(function ($course, $cm = null) {
    global $CFG, $SESSION;
    if (!isloggedin()) {
        $SESSION->wantsurl = $CFG->wwwroot . SLUG . str_replace($CFG->wwwroot, '', qualified_me());
    }
    require_course_login($course, true, $cm, false);
});
