<?php
require('../../config.php');
require_login();

global $DB, $USER;

// school id
$schoolid = required_param('id', PARAM_INT);
require_sesskey();

$context = context_system::instance();

// Only RM or admin can accept
if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context)) {
    redirect('/', 'Access denied');
}

// Get RM mapping
$record = $DB->get_record('rm_school_map', [
    'schoolid' => $schoolid,
    'rmid' => $USER->id
], '*', MUST_EXIST);

// Update status -> Accepted
$record->status = 1; // 0 = pending, 1 = accepted, 2 = rejected
$DB->update_record('rm_school_map', $record);

redirect(new moodle_url('/local/studentmanagement/schools.php'), 'School Accepted Successfully');