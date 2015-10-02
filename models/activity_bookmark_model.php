<?php

use Functional as F;

defined('MOODLE_INTERNAL') || die();

class activity_bookmark_model {

    /**
     * get the most recently viewed activity, for a given user in a given course
     * @global moodle_database
     * @param int $userid
     * @param int $courseid
     * @param course_modinfo $modinfo
     * @return array|null
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function get_most_recent_activity($userid, $courseid, course_modinfo $modinfo) {
        global $DB;

        // get all activities that are available
        $activities = $this->_get_available_activities($modinfo->get_cms());
        if (empty($activities)) {
            return null;
        }

        // generate the sql and params
        list($in_sql, $in_params) = $DB->get_in_or_equal(F\pluck($activities, 'id'), $type=SQL_PARAMS_NAMED);
        $sql = $this->_get_recent_activity_sql($in_sql);
        $params = array_merge([
            'userid' => $userid,
            'context_module' => CONTEXT_MODULE,
            'courseid' => $courseid
        ], $in_params);

        // execute the query
        $mod = $DB->get_record_sql($sql, $params);
        return $mod === false ? null : [$mod->modname, $mod->id];
    }

    /**
     * get the first activity that is visible and available from a modinfo object
     * @param course_modinfo $modinfo
     * @return array|null
     */
    public function get_first_activity(course_modinfo $modinfo) {
        $activities = $this->_get_available_activities($modinfo->get_cms());
        $mod = F\first($activities);
        return $mod === null ? null : [$mod->modname, $mod->id];
    }

    /**
     * @param string $in_sql
     * @return string
     */
    protected function _get_recent_activity_sql($in_sql) {
        $sql = <<<SQL
            SELECT cm.id AS id, m.name AS modname
            FROM {logstore_standard_log} l
            INNER JOIN {course_modules} cm ON cm.id = l.contextinstanceid
            INNER JOIN {modules} m ON m.id = cm.module
            WHERE l.userid = :userid
                AND l.contextlevel = :context_module
                AND l.courseid = :courseid
                AND l.action = 'viewed'
                AND cm.id $in_sql
                AND m.name != 'url'
            ORDER BY l.timecreated DESC
            LIMIT 1
SQL;
        return $sql;
    }

    /**
     * get all activities that are visible and available
     * @param array $cms
     * @return array
     */
    protected function _get_available_activities(array $cms) {
        return F\filter($cms, function ($a) {
            return $a->visible == true && $a->available == true;
        });
    }

}
