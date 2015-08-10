<?php

use Mockery as m;
use Symfony\Component\HttpKernel\Client;

defined('MOODLE_INTERNAL') || die();

class activity_model_web_test extends advanced_testcase {

    /**
     * @var stdClass
     */
    protected $_user;

    /**
     * @var stdClass
     */
    protected $_course;

    /**
     * @var activity_bookmark_model
     */
    protected $_abm;

    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * setUp
     */
    public function setUp() {
        if (!defined('SLUG')) {
            define('SLUG', '');
        }

        // create a user
        $this->_user = $this->getDataGenerator()->create_user();

        // create a course
        $this->_course = $this->getDataGenerator()->create_course();

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($this->_user->id, $this->_course->id);

        // login the user
        $this->setUser($this->_user);

        // create the Silex app
        $this->_app = require __DIR__ . '/../app.php';

        // mock the model
        $this->_abm = m::mock('activity_bookmark_model');
        $this->_app['activity_bookmark_model'] = $this->_app->share(function ($app) {
            return $this->_abm;
        });

        // reset the database after each test
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        m::close();
    }

    /**
     * ensures the 'wantsurl' gets set if the user is not logged in when the first request is made
     */
    public function test_not_logged_in_sets_wantsurl() {
        global $SESSION;

        // logout user
        $this->setUser(null);
        $this->assertFalse(property_exists($SESSION, 'wantsurl'));

        // make the request
        $client = new Client($this->_app);
        $client->request('GET', sprintf('/%d', $this->_course->id + 1));

        // ensure the 'wantsurl' has been set
        $this->assertTrue(property_exists($SESSION, 'wantsurl'));
    }

    /**
     * when the course does not exist, the user should receive a 404 response
     */
    public function test_get_course_does_not_exist() {
        // make the request
        $client = new Client($this->_app);
        $client->request('GET', sprintf('/%d', $this->_course->id + 1));

        // test the response
        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertContains(
            get_string('course_not_found', 'local_activity_bookmark', $this->_course->id + 1),
            $client->getResponse()->getContent()
        );
    }

    /**
     * with the 'manageactivities' permission, the user should be redirected to the course view page
     */
    public function test_get_course_user_can_manage_activities() {
        global $CFG;
        $roleid = $this->getDataGenerator()->create_role();
        $this->loadDataSet($this->createArrayDataSet([
           'role_capabilities' => [
               ['contextid', 'roleid', 'capability', 'permission', 'timemodified'],
               [context_system::instance()->id, $roleid, 'moodle/course:manageactivities', CAP_ALLOW, time()]
           ]
        ]));
        $this->getDataGenerator()->enrol_user($this->_user->id, $this->_course->id, $roleid);
        $this->setUser($this->_user);

        // make the request
        $client = new Client($this->_app);
        $client->request('GET', sprintf('/%d', $this->_course->id));

        // test the response
        $this->assertTrue($client->getResponse()->isRedirect(
            sprintf('%s/course/view.php?id=%d', $CFG->wwwroot, $this->_course->id)
        ));
    }

    /**
     * when the most recent activity is returned, the user should be redirected to its mod view page
     */
    public function test_get_course_most_recent_activity() {
        global $CFG;
        $this->getDataGenerator()->enrol_user($this->_user->id, $this->_course->id);

        // mocks
        $this->_abm->shouldReceive('get_most_recent_activity')->once()->with(
            $this->_user->id, $this->_course->id, $this->_app['get_fast_modinfo']($this->_course->id)
        )->andReturn(['foo', 301]);

        // make the request
        $client = new Client($this->_app);
        $client->request('GET', sprintf('/%d', $this->_course->id));

        // test the response
        $this->assertTrue($client->getResponse()->isRedirect(
            sprintf('%s/mod/foo/view.php?id=301', $CFG->wwwroot)
        ));
    }

    /**
     * when the first activity is returned, the user should be redirected to its mod view page
     */
    public function test_get_course_first_activity() {
        global $CFG;
        $this->getDataGenerator()->enrol_user($this->_user->id, $this->_course->id);

        // mocks
        $this->_abm->shouldReceive('get_most_recent_activity')->once()->with(
            $this->_user->id, $this->_course->id, $this->_app['get_fast_modinfo']($this->_course->id)
        )->andReturn(null);
        $this->_abm->shouldReceive('get_first_activity')->once()->with(
            $this->_app['get_fast_modinfo']($this->_course->id)
        )->andReturn(['bar', 302]);

        // make the request
        $client = new Client($this->_app);
        $client->request('GET', sprintf('/%d', $this->_course->id));

        // test the response
        $this->assertTrue($client->getResponse()->isRedirect(
            sprintf('%s/mod/bar/view.php?id=302', $CFG->wwwroot)
        ));
    }

    /**
     * when an activity is not returned, the user should receive a 404 response
     */
    public function test_get_course_no_activities() {
        $this->getDataGenerator()->enrol_user($this->_user->id, $this->_course->id);

        // mocks
        $this->_abm->shouldReceive('get_most_recent_activity')->once()->andReturn(null);
        $this->_abm->shouldReceive('get_first_activity')->once()->andReturn(null);

        // make the request
        $client = new Client($this->_app);
        $client->request('GET', sprintf('/%d', $this->_course->id));

        // test the response
        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertContains(
            get_string('activity_not_found', 'local_activity_bookmark', $this->_course->id),
            $client->getResponse()->getContent()
        );
    }

}
