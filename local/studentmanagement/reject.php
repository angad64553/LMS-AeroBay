<?php
require('../../config.php');

require_login();

$userid = required_param('id', PARAM_INT);

global $DB;

// Get user
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

// Suspend user (Reject)
$user->suspended = 1;

$DB->update_record('user', $user);

// Redirect back to student management page
redirect(
    new moodle_url('/local/studentmanagement/index.php'),
    'User Rejected Successfully'
);