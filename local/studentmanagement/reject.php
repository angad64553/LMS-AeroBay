<?php
require('../../config.php');

$userid = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

global $DB;

// Get enrol instance for this course
$enrol = $DB->get_record('enrol', ['courseid' => $courseid], '*', IGNORE_MULTIPLE);

if ($enrol) {

    // Get enrol plugin
    $enrolplugin = enrol_get_plugin($enrol->enrol);

    if ($enrolplugin) {
        // Proper Moodle API unenrol
        $enrolplugin->unenrol_user($enrol, $userid);
    }
}

redirect(new moodle_url('/local/studentmanagement/index.php', ['id' => $courseid]), 'User Rejected');