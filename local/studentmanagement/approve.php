<?php
require('../../config.php');
require_login();

global $DB, $CFG;

require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/enrollib.php');

$userid = required_param('id', PARAM_INT);

// USER
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// STUDENT MAP
$students = $DB->get_records('local_studentmanagement', ['userid' => $userid]);
$student = reset($students);

$schoolid = $student->schoolid;
$gradeid  = $student->gradeid;

// COURSE MAP
$mapping = $DB->get_records('school_course_map', [
    'schoolid' => $schoolid,
    'gradeid'  => $gradeid
]);
$mapping = reset($mapping);
$courseid = $mapping->courseid;

// ENROL INSTANCE
$enrol = $DB->get_record('enrol', [
    'courseid' => $courseid,
    'enrol'    => 'manual'
], '*', MUST_EXIST);

// ================= PASSWORD FIX =================

// ❗ SAME FUNCTION use करो (random_string OK है)
$newpassword = random_string(8);

echo "USERNAME: " . $user->username . "<br>";
echo "PASSWORD: " . $newpassword;
die;

update_internal_user_password($user, $newpassword);



// USER ACTIVATE (same old working style)
$user->suspended = 0;
$DB->update_record('user', $user);

// ================= ENROL =================
$enrolplugin = enrol_get_plugin('manual');

$existing = $DB->get_record('user_enrolments', [
    'userid' => $userid,
    'enrolid' => $enrol->id
]);

if (!$existing) {
    $enrolplugin->enrol_user($enrol, $userid);
} else {
    $existing->status = 0;
    $DB->update_record('user_enrolments', $existing);
}

// ================= EMAIL (UNCHANGED WORKING) =================
$subject = "Account Approved - LMS";

$message = "
Hello $user->firstname,

Your account has been approved.

Login URL: http://localhost/moodle/login/index.php
Username: $user->username
Password: $newpassword

Please login and change your password.

Regards,
LMS Team
";

email_to_user($user, core_user::get_noreply_user(), $subject, $message);

// ================= REDIRECT =================
redirect(new moodle_url('/local/studentmanagement/index.php'), 'Student Approved & Email Sent');