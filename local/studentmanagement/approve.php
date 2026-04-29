<?php
require('../../config.php');

$userid = required_param('id', PARAM_INT);

require_login();

global $DB;

//  FIX: multiple records handle karo
$students = $DB->get_records('local_studentmanagement', ['userid' => $userid]);

if (!$students) {
    print_error('Student not found');
}

//  first record le lo
$student = reset($students);

$schoolid = $student->schoolid;
$gradeid  = $student->gradeid;

// 2. Mapping
$mapping = $DB->get_record('school_course_map', [
    'schoolid' => $schoolid,
    'gradeid'  => $gradeid
]);

if (!$mapping) {
    print_error('No course mapping found');
}

$courseid = $mapping->courseid;

// 3. Enrol instance
$enrol = $DB->get_record('enrol', [
    'courseid' => $courseid,
    'enrol'    => 'manual'
]);

if (!$enrol) {
    print_error('Enrol instance not found');
}

// 4. Check existing enrolment
$existing = $DB->get_record('user_enrolments', [
    'userid' => $userid,
    'enrolid' => $enrol->id
]);

$enrolplugin = enrol_get_plugin('manual');

if (!$existing) {
    $enrolplugin->enrol_user($enrol, $userid);
} else {
    $existing->status = 0;
    $DB->update_record('user_enrolments', $existing);
}

// 5. Unsuspend user
$DB->set_field('user', 'suspended', 0, ['id' => $userid]);

// 6. Redirect
redirect(new moodle_url('/local/studentmanagement/index.php'), 'Student Approved & Enrolled');