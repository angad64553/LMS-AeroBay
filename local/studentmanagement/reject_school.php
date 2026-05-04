<?php
require('../../config.php');
require_login();

global $DB, $USER;

$schoolid = required_param('id', PARAM_INT);
require_sesskey();

$context = context_system::instance();

// Security check
if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context)) {
    redirect('/', 'Access denied');
}

// Get RM mapping
$record = $DB->get_record('rm_school_map', [
    'schoolid' => $schoolid,
    'rmid' => $USER->id
], '*', MUST_EXIST);

// Update status -> Rejected
$record->status = 2; // 0 = pending, 1 = accepted, 2 = rejected
$DB->update_record('rm_school_map', $record);

redirect(new moodle_url('/local/studentmanagement/schools.php'), 'School Rejected Successfully');