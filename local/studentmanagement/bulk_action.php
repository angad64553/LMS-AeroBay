<?php
require('../../config.php');

$courseid = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$userids = optional_param_array('userid', [], PARAM_INT);

require_login($courseid);

global $DB;

if (!empty($userids)) {

    foreach ($userids as $userid) {

        //  Get exact enrolment record
        $ue = $DB->get_record_sql("
            SELECT ue.*
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE ue.userid = ? AND e.courseid = ?
        ", [$userid, $courseid]);

        if ($ue) {

            if ($action == 'approve') {

                //  Activate user (NOT enrol_user)
                $start = time();

                $enrol = $DB->get_record('enrol', ['id' => $ue->enrolid]);
                $duration = $enrol->enrolperiod;

                $end = ($duration > 0) ? $start + $duration : 0;

                $ue->status = 0;
                $ue->timestart = $start;
                $ue->timeend = $end;

                $DB->update_record('user_enrolments', $ue);

            } elseif ($action == 'reject') {

                //  Proper unenrol using Moodle API
                $enrol = $DB->get_record('enrol', ['id' => $ue->enrolid]);
                $plugin = enrol_get_plugin($enrol->enrol);

                if ($plugin) {
                    $plugin->unenrol_user($enrol, $userid);
                }
            }
        }
    }
}

// redirect back
redirect(
    new moodle_url('/local/studentmanagement/index.php', ['id' => $courseid]),
    'Bulk action completed successfully'
);