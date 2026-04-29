<?php
require('../../config.php');

require_login();

$userid = required_param('id', PARAM_INT);

global $DB;

// ❗ user exist check
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// ❗ delete from custom table
$DB->delete_records('local_studentmanagement', ['userid' => $userid]);

// ❗ delete enrolments (important)
$enrolments = $DB->get_records('user_enrolments', ['userid' => $userid]);

foreach ($enrolments as $enrol) {
    $DB->delete_records('user_enrolments', ['id' => $enrol->id]);
}

// ❗ delete user (main)
$DB->delete_records('user', ['id' => $userid]);

// redirect
redirect(new moodle_url('/local/studentmanagement/index.php'), 'User Deleted Successfully');