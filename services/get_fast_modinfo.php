<?php

defined('MOODLE_INTERNAL') || die();

$app['get_fast_modinfo'] = $app->protect(function ($courseid) {
    return get_fast_modinfo($courseid);
});
