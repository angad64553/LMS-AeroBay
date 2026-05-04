<?php
require('../../config.php');

require_login();

global $DB, $CFG;

require_once($CFG->dirroot.'/user/lib.php');

$userid = required_param('id', PARAM_INT);
require_sesskey();

// Get user
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

$transaction = $DB->start_delegated_transaction();

// Remove custom student mapping and delete the Moodle user safely.
$DB->delete_records('local_studentmanagement', ['userid' => $userid]);
user_delete_user($user);

$transaction->allow_commit();

// Redirect back to student management page
redirect(
    new moodle_url('/local/studentmanagement/index.php'),
    'User rejected and removed successfully'
);
