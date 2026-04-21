<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/message/lib.php');

require_login();

global $DB, $USER, $PAGE;

// ✅ params
$courseid = required_param('courseid', PARAM_INT);
$userids = required_param('userids', PARAM_TEXT);

// ✅ page setup (important)
$PAGE->set_url('/blocks/configurable_reports/send_notification.php');

// convert ids to array
$userids_array = explode(',', $userids);

$count = 0;

foreach ($userids_array as $userid) {

    if (empty($userid)) continue;

    $touser = $DB->get_record('user', ['id' => (int)$userid]);

    if (!$touser) continue;

    // ✅ MESSAGE OBJECT
    $message = new \core\message\message();
    $message->component = 'moodle';
    $message->name = 'instantmessage';
    $message->userfrom = $USER;
    $message->userto = $touser;

    // 🔥 MESSAGE CONTENT
    $message->subject = "Quiz Reminder";
    $message->fullmessage = "Hi " . $touser->firstname . ", please complete your pending quiz.";
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = "<p>Hi <b>" . $touser->firstname . "</b>, please complete your pending quiz.</p>";
    $message->smallmessage = "Quiz reminder";

    $message->notification = 1; // 🔥 important

    // send
    $result = message_send($message);

if (!empty($result)) {
    $count++;
}
}

//  redirect back with message
redirect(
    new moodle_url('/blocks/configurable_reports/viewreport.php', [
        'id' => 2,
        'courseid' => $courseid
    ]),
    "Notification sent successfully",
    2
);