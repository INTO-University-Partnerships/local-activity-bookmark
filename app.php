<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// bootstrap Moodle
require_once __DIR__ . '/../../config.php';

// create a Silex app
require_once __DIR__ . '/../../vendor/autoload.php';
$app = new Silex\Application();
$app['debug'] = debugging('', DEBUG_MINIMAL);

// require the services
foreach ([
    'require_course_login',
    'get_fast_modinfo',
    'activity_bookmark_model',
] as $service) {
    require __DIR__ . '/services/' . $service . '.php';
}

// get course route
$app->get('/{courseid}', function ($courseid) use ($app) {
    global $USER, $CFG;

    // require login
    try {
        $app['require_course_login']($courseid);
    } catch (dml_missing_record_exception $e) {
        throw new NotFoundHttpException(get_string('course_not_found', 'local_activity_bookmark', $courseid));
    }

    // redirect to the Moodle course view page if the user has permission to manage activities
    if (has_capability('moodle/course:manageactivities', context_course::instance($courseid), $USER)) {
        return new RedirectResponse(sprintf('%s/course/view.php?id=%d', $CFG->wwwroot, $courseid));
    }

    /** @var activity_bookmark_model $abm */
    $abm = $app['activity_bookmark_model'];

    // get the most recent activity
    $recent = $abm->get_most_recent_activity($USER->id, $courseid, $app['get_fast_modinfo']($courseid));
    $activity = $recent ? $recent : $abm->get_first_activity($app['get_fast_modinfo']($courseid));
    if ($activity === null) {
        throw new NotFoundHttpException(get_string('activity_not_found', 'local_activity_bookmark', $courseid));
    }

    // redirect the user
    list($mod, $id) = $activity;
    return new RedirectResponse(sprintf('%s/mod/%s/view.php?id=%d', $CFG->wwwroot, $mod, $id));
})
->assert('courseid', '\d+');

// handle "not found" exceptions
$app->error(function (NotFoundHttpException $e, $code) use ($app) {
    global $OUTPUT, $PAGE, $CFG;
    $PAGE->set_url($CFG->wwwroot . $app['request']->getRequestURI());
    return new Response(
        $OUTPUT->header() . $OUTPUT->notification($e->getMessage()) . $OUTPUT->footer(),
        $e->getStatusCode()
    );
});

return $app;
