<?php

use Functional as F;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/activity_bookmark_model.php';

class activity_bookmark_model_test extends advanced_testcase {

    /**
     * @var activity_bookmark_model
     */
    protected $_cut;

    /**
     * setUp
     */
    protected function setUp() {
        global $CFG;
        $CFG->enableavailability = true;
        $this->_cut = new activity_bookmark_model();
        $this->resetAfterTest();
    }

    /**
     * test instantiation
     */
    public function test_instantiation() {
        $this->assertInstanceOf('activity_bookmark_model', $this->_cut);
    }

    /**
     * get the most recent activity when nothing has been logged
     */
    public function test_get_most_recent_activity_no_logs() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->assertNull(
            $this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id))
        );
    }

    /**
     * get the most recent activity with a single log entry
     */
    public function test_get_most_recent_activity_single_log_entry() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $mod = $this->_create_module('quiz', $course);

        // create one entry in the logs
        $this->loadDataSet($this->createArrayDataSet([
            'logstore_standard_log' => [
                ['id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'],
                [1, 0, $course->id, $user->id, $mod['contextid'], CONTEXT_MODULE, $mod['id'], 'viewed', time()]
            ]
        ]));
        $actual = $this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id));
        $this->assertInternalType('array', $actual);
        $this->assertEquals(['quiz', $mod['id']], $actual);
    }

    /**
     * get the most recent activity when there is plenty in the logs
     */
    public function test_get_most_recent_activity_when_multiple_log_entries() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $mods = [];
        F\each(['quiz', 'forum', 'wiki'], function ($m) use ($course, &$mods) {
            $mods[$m] = $this->_create_module($m, $course);
        });

        // create multiple entries in the logs
        $now = time();
        $this->loadDataSet($this->createArrayDataSet([
            'logstore_standard_log' => [
                ['id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'],
                [1, 0, $course->id, $user->id, $mods['quiz']['contextid'], CONTEXT_MODULE, $mods['quiz']['id'], 'viewed', $now - 3],
                [2, 0, $course->id, $user->id, $mods['forum']['contextid'], CONTEXT_MODULE, $mods['forum']['id'], 'viewed', $now - 1],
                [3, 0, $course->id, $user->id, $mods['wiki']['contextid'], CONTEXT_MODULE, $mods['wiki']['id'], 'viewed', $now - 2],
                [4, 0, $course->id, $user->id, $mods['quiz']['contextid'], CONTEXT_MODULE, $mods['quiz']['id'], 'viewed', $now - 4],
                [5, 0, $course->id, $user->id, $mods['forum']['contextid'], CONTEXT_MODULE, $mods['forum']['id'], 'viewed', $now - 5],
                [6, 0, $course->id, $user->id, $mods['wiki']['contextid'], CONTEXT_MODULE, $mods['wiki']['id'], 'viewed', $now - 6],
            ]
        ]));
        $actual = $this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id));
        $this->assertInternalType('array', $actual);
        $this->assertEquals(['forum', $mods['forum']['id']], $actual);
    }

    /**
     * get the most recent activity when the only log entry relates to another course
     */
    public function test_get_most_recent_activity_filtered_by_course() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $mod = $this->_create_module('quiz', $course);

        // create one entry in the logs
        $this->loadDataSet($this->createArrayDataSet([
            'logstore_standard_log' => [
                ['id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'],
                [1, 0, $course->id, $user->id, $mod['contextid'], CONTEXT_MODULE, $mod['id'], 'viewed', time()]
            ]
        ]));
        $course2 = $this->getDataGenerator()->create_course();
        $this->assertNull($this->_cut->get_most_recent_activity($user->id, $course2->id, get_fast_modinfo($course->id)));
    }

    /**
     * get the most recent activity when the only log entry relates to another user
     */
    public function test_get_most_recent_activity_filtered_by_user() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $mod = $this->_create_module('quiz', $course);

        // create one entry in the logs
        $this->loadDataSet($this->createArrayDataSet([
            'logstore_standard_log' => [
                ['id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'],
                [1, 0, $course->id, $user->id, $mod['contextid'], CONTEXT_MODULE, $mod['id'], 'viewed', time()]
            ]
        ]));

        $user2 = $this->getDataGenerator()->create_user();
        $this->assertNull($this->_cut->get_most_recent_activity($user2->id, $course->id, get_fast_modinfo($course->id)));
    }

    /**
     * get the most recent activity when the only log entry relates to another context level
     */
    public function test_get_most_recent_activity_filtered_by_context_level() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->_create_module('quiz', $course);

        // create one entry in the logs
        $this->loadDataSet($this->createArrayDataSet([
            'logstore_standard_log' => [
                ['id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'],
                [1, 0, $course->id, $user->id, context_course::instance($course->id)->id, CONTEXT_COURSE, $course->id, 'viewed', time()]
            ]
        ]));
        $this->assertNull($this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id)));
    }

    /**
     * get the most recent activity when the most recent log entry is for a module that is not visible
     */
    public function test_get_most_recent_activity_when_first_is_not_visible() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->_create_module('quiz', $course, $section=0);
        $forum = $this->_create_module('forum', $course, $section=0, $visible=0);  //  not visible

        // create 2 entries in the logs
        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'logstore_standard_log' => array(
                array('id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'),
                array(1, 0, $course->id, $user->id, $quiz['contextid'], CONTEXT_MODULE, $quiz['id'], 'viewed', $now - 3),
                array(2, 0, $course->id, $user->id, $forum['contextid'], CONTEXT_MODULE, $forum['id'], 'viewed', $now - 1),
            )
        )));
        $actual = $this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id));
        $this->assertInternalType('array', $actual);
        $this->assertEquals(['quiz', $quiz['id']], $actual);
    }

    /**
     * get the most recent activity when the most recent log entry is for a module that is not available
     */
    public function test_get_most_recent_activity_when_first_is_not_available() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $availability = [
            'op' => core_availability\tree::OP_AND,
            'c' => [availability_date\condition::get_json('>=', time() + 24 * 3600)], // not yet available
            'showc' => [true]
        ];
        $quiz = $this->_create_module('quiz', $course, $section=0);
        $forum = $this->_create_module('forum', $course, $section=0, $visible=1, $availability=json_encode($availability));  //  not available

        // create 2 entries in the logs
        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'logstore_standard_log' => array(
                array('id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'),
                array(1, 0, $course->id, $user->id, $quiz['contextid'], CONTEXT_MODULE, $quiz['id'], 'viewed', $now - 3),
                array(2, 0, $course->id, $user->id, $forum['contextid'], CONTEXT_MODULE, $forum['id'], 'viewed', $now - 1),
            )
        )));
        $actual = $this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id));
        $this->assertInternalType('array', $actual);
        $this->assertEquals(['quiz', $quiz['id']], $actual);
    }

    /**
     * tests getting the most recent activity ignores mod/url instances
     */
    public function test_get_most_recent_activity_ignores_mod_url() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $mods = [];
        F\each(['url', 'quiz'], function ($m) use ($course, &$mods) {
            $mods[$m] = $this->_create_module($m, $course);
        });

        // create multiple entries in the logs (of which the most recent is an instance of mod/url)
        $now = time();
        $this->loadDataSet($this->createArrayDataSet([
            'logstore_standard_log' => [
                ['id', 'edulevel', 'courseid', 'userid', 'contextid', 'contextlevel', 'contextinstanceid', 'action', 'timecreated'],
                [2, 0, $course->id, $user->id, $mods['url']['contextid'], CONTEXT_MODULE, $mods['url']['id'], 'viewed', $now - 3],
                [3, 0, $course->id, $user->id, $mods['quiz']['contextid'], CONTEXT_MODULE, $mods['quiz']['id'], 'viewed', $now - 2],
                [4, 0, $course->id, $user->id, $mods['url']['contextid'], CONTEXT_MODULE, $mods['url']['id'], 'viewed', $now - 1],
                [5, 0, $course->id, $user->id, $mods['quiz']['contextid'], CONTEXT_MODULE, $mods['quiz']['id'], 'viewed', $now - 4],
            ]
        ]));
        $actual = $this->_cut->get_most_recent_activity($user->id, $course->id, get_fast_modinfo($course->id));
        $this->assertInternalType('array', $actual);
        $this->assertNotEquals('url', F\head($actual));
        $this->assertEquals(['quiz', $mods['quiz']['id']], $actual);
    }

    /**
     * get the first activity in the course (all are visible and available)
     */
    public function test_get_first_activity() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 6]);
        $this->setUser($user);

        // section 3
        $this->_create_module('forum', $course, $section=3);

        // section 2 (section 1 is empty)
        $this->_create_module('folder', $course, $section=2);
        $this->_create_module('label', $course, $section=2);

        // section 0
        $quiz = $this->_create_module('quiz', $course, $section=0);
        $this->_create_module('scorm', $course, $section=0);

        // modinfo
        $minfo = course_modinfo::instance($course->id, $user->id);
        $this->assertCount(5, $minfo->get_cms());

        $actual = $this->_cut->get_first_activity($minfo);
        $this->assertInternalType('array', $actual);
        $this->assertEquals(['quiz', $quiz['id']], $actual);
    }

    /**
     * get the first activity in the course, when the first sequential activity is not visible
     */
    public function test_get_first_activity_visible_only() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 8]);
        $this->setUser($user);
        $this->_create_module('quiz', $course, $section=3, $visible=0);
        $folder = $this->_create_module('folder', $course, $section=7);

        // modinfo
        $minfo = course_modinfo::instance($course->id, $user->id);
        $this->assertEquals(2, count($minfo->get_cms()));
        $actual = $this->_cut->get_first_activity($minfo);

        $this->assertInternalType('array', $actual);
        $this->assertEquals(['folder', $folder['id']], $actual);
    }

    /**
     * get the first activity in the course, when there are no activities
     */
    public function test_get_first_activity_empty_course() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 4]);
        $this->setUser($user);

        // modinfo
        $minfo = course_modinfo::instance($course->id, $user->id);

        $this->assertNull($this->_cut->get_first_activity($minfo));
    }

    /**
     * get the first activity in the course, when the first activity is unavailable
     */
    public function test_get_first_activity_unavailable() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 4]);
        $this->setUser($user);
        $availability = [
            'op' => core_availability\tree::OP_AND,
            'c' => [availability_date\condition::get_json('>=', time() + 24 * 3600)], // not yet available
            'showc' => [true]
        ];
        $this->_create_module('quiz', $course, $section=3, $visible=1, $availability=json_encode($availability));
        $folder = $this->_create_module('folder', $course, $section=7);

        // modinfo
        $minfo = course_modinfo::instance($course->id, $user->id);
        $this->assertEquals(2, count($minfo->get_cms()));

        $actual = $this->_cut->get_first_activity($minfo);
        $this->assertInternalType('array', $actual);
        $this->assertEquals(['folder', $folder['id']], $actual);
    }

    /**
     * get the first activity in the course, when the only activities are either not visible or unavailable
     */
    public function test_get_first_activity_neither_visible_nor_available() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 4]);
        $this->setUser($user);
        $availability = [
            'op' => core_availability\tree::OP_AND,
            'c' => [availability_date\condition::get_json('>=', time() + 24 * 3600)], // not yet available
            'showc' => [true]
        ];
        $this->_create_module('quiz', $course, $section=1, $visible=0);
        $this->_create_module('scorm', $course, $section=2, $visible=1, $availability=json_encode($availability));

        // modinfo
        $minfo = course_modinfo::instance($course->id, $user->id);

        $this->assertNull($this->_cut->get_first_activity($minfo));
    }

    /**
     * @param string $module
     * @param stdClass $course
     * @param int $section
     * @param int $visible
     * @param string|null $availability
     * @return array
     */
    protected function _create_module($module, $course, $section=0, $visible=1, $availability=null) {
        $mod = $this->getDataGenerator()->create_module($module, [
            'course' => $course,
            'section' => $section,
            'visible' => $visible,
            'availability' => $availability
        ]);
        return [
            'id' => $mod->cmid,
            'contextid' => context_module::instance($mod->cmid)->id,
            'section' => $section
        ];
    }

}
