<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_studentmanagement_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026050600) {
        $table = new xmldb_table('school_course_map');
        $oldindex = new xmldb_index('unique_map', XMLDB_INDEX_UNIQUE, ['schoolid', 'gradeid']);
        $newindex = new xmldb_index('school_grade_course_uix', XMLDB_INDEX_UNIQUE,
            ['schoolid', 'gradeid', 'courseid']);

        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }

        if (!$dbman->index_exists($table, $newindex)) {
            $dbman->add_index($table, $newindex);
        }

        upgrade_plugin_savepoint(true, 2026050600, 'local', 'studentmanagement');
    }

    return true;
}
