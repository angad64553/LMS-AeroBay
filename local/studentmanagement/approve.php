<?php
require('../../config.php');
require_login();

global $DB, $CFG;

require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/message/lib.php');

$userid = required_param('id', PARAM_INT);
require_sesskey();

// Fetch fresh user and make sure Moodle can find it during normal login.
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$user->auth = 'manual';
$user->mnethostid = $CFG->mnet_localhost_id;
$user->confirmed = 1;
$user->suspended = 0;

// ================= PASSWORD GENERATE =================
//  FIXED: single source of truth
$newpassword = random_string(8);

// ================= ACTIVATE USER =================
user_update_user($user, false);

//  UPDATE PASSWORD (NO OUTPUT BEFORE THIS)
update_internal_user_password($user, $newpassword);

// Reload the record after updating auth fields and password.
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// ================= GET STUDENT MAPPING =================
$students = $DB->get_records('local_studentmanagement', ['userid' => $userid]);
$student = reset($students);

if (!$student) {
    redirect(new moodle_url('/local/studentmanagement/index.php'), 'Student mapping not found');
}

$schoolid = $student->schoolid;
$gradeid  = $student->gradeid;

// ================= GET COURSE =================
$mapping = $DB->get_records('school_course_map', [
    'schoolid' => $schoolid,
    'gradeid'  => $gradeid
]);

$mapping = reset($mapping);

if (!$mapping) {
    redirect(new moodle_url('/local/studentmanagement/index.php'), 'Course mapping not found');
}

$courseid = $mapping->courseid;

// ================= AUTO ENROL =================
$enrol = enrol_get_plugin('manual');
$instances = enrol_get_instances($courseid, true);

if (!empty($instances)) {
    $instance = reset($instances);
    $enrol->enrol_user($instance, $userid, 5); // student role
}

// ================= SEND EMAIL =================
$message = new \core\message\message();
$message->component         = 'local_studentmanagement';
$message->name              = 'student_approved';
$message->userfrom          = core_user::get_support_user();
$message->userto            = $user;
$message->subject           = 'Account Approved';
$message->fullmessage       = "Your account has been approved.\nUsername: {$user->username}\nPassword: {$newpassword}";
$message->fullmessageformat = FORMAT_PLAIN;
$message->fullmessagehtml   = '';
$message->smallmessage      = 'Account approved';
$message->notification      = 1;
$message->courseid          = SITEID;

if (message_send($message)) {
    $msg = "Student approved successfully.";
} else {
    $msg = "Student approved successfully, but the notification could not be sent.";
}

// ================= REDIRECT =================
redirect(new moodle_url('/local/studentmanagement/index.php'), $msg);
