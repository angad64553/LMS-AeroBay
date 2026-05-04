<?php
require('../../config.php');

require_login();

global $DB, $CFG;

require_once($CFG->dirroot.'/user/lib.php');

$action = required_param('action', PARAM_ALPHA);
$userids = optional_param_array('userid', [], PARAM_INT);
require_sesskey();

if (!empty($userids)) {

    foreach ($userids as $userid) {

        // Get user record
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);

        if ($user) {

            if ($action == 'approve') {

                // Activate user
                $user->suspended = 0;

            } elseif ($action == 'reject') {

                // Remove custom student mapping and delete the Moodle user safely.
                $DB->delete_records('local_studentmanagement', ['userid' => $userid]);
                user_delete_user($user);
                continue;
            }

            $DB->update_record('user', $user);
        }
    }
}

// redirect back (NO course id now)
redirect(
    new moodle_url('/local/studentmanagement/index.php'),
    'Bulk action completed successfully'
);
