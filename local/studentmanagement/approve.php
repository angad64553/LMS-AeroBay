<?php
require('../../config.php');

$userid = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

global $DB;

// Correct record fetch
$ue = $DB->get_record_sql("
SELECT ue.*
FROM {user_enrolments} ue
JOIN {enrol} e ON e.id = ue.enrolid
WHERE ue.userid = ? AND e.courseid = ?", [$userid, $courseid], MUST_EXIST);

// Activate user
$ue->status = 0;
$DB->update_record('user_enrolments', $ue);

redirect(new moodle_url('/local/studentmanagement/index.php', ['id' => $courseid]), 'User Approved');