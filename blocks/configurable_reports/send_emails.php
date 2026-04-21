<?php
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/message/lib.php'); // 🔥 IMPORTANT

require_login();

global $PAGE, $USER, $DB, $COURSE;

$context = context_course::instance($COURSE->id);
$PAGE->set_context($context);

if (!has_capability('block/configurable_reports:managereports', $context) &&
    !has_capability('block/configurable_reports:manageownreports', $context)) {
    throw new moodle_exception('badpermissions');
}

// ✅ Get params safely
$userids = optional_param_array('userids', [], PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

/**
 * Form class
 */
class sendemail_form extends moodleform {

    public function definition(): void {
        global $COURSE;

        $mform = $this->_form;
        $context = context_course::instance($COURSE->id);

        $editoroptions = [
            'trusttext' => true,
            'subdirs' => true,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'context' => $context,
        ];

        $mform->addElement('hidden', 'usersids', $this->_customdata['usersids']);
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);

        $mform->addElement('text', 'subject', 'Notification Subject');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'content', 'Notification Message', null, $editoroptions);

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'send', 'Send Notification');
        $buttons[] = $mform->createElement('cancel');

        $mform->addGroup($buttons, 'buttons', 'Actions', [' '], false);
    }
}

// Create form
$form = new sendemail_form(null, [
    'usersids' => implode(',', $userids),
    'courseid' => $courseid,
]);

// Cancel
if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php?id=' . $courseid));
}

// Submit
else if ($data = $form->get_data()) {

    foreach (explode(',', $data->usersids) as $userid) {

        if (empty($userid)) continue;

        $touser = $DB->get_record('user', ['id' => (int)$userid]);

        if (!$touser) continue;

        // 🔥 Notification object
        $eventdata = new \core\message\message();
        $eventdata->component         = 'moodle';
        $eventdata->name              = 'instantmessage';
        $eventdata->userfrom          = $USER;
        $eventdata->userto            = $touser;
        $eventdata->subject           = $data->subject;
        $eventdata->fullmessage       = $data->content['text'];
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $data->content['text'];
        $eventdata->smallmessage      = $data->subject;

        // ✅ SEND NOTIFICATION
        message_send($eventdata);
    }

    // Redirect back
    redirect(new moodle_url('/course/view.php?id=' . $data->courseid), 'Notification Sent!');
}

// Page UI
$PAGE->set_title('Send Notification');
$PAGE->set_heading(format_string($COURSE->fullname));

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'no-overflow']);
$form->display();
echo html_writer::end_tag('div');

echo $OUTPUT->footer();