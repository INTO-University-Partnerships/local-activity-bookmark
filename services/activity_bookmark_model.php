<?php

defined('MOODLE_INTERNAL') || die();

$app['activity_bookmark_model'] = $app->share(function ($app) {
    require_once __DIR__ . '/../models/activity_bookmark_model.php';
    $activity_bookmark_model = new activity_bookmark_model();
    return $activity_bookmark_model;
});
