<?php
require('../../config.php');

require_login();

$action = required_param('action', PARAM_ALPHA);
$userids = optional_param_array('userid', [], PARAM_INT);

global $DB;

if (!empty($userids)) {

    foreach ($userids as $userid) {

        // Get user record
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);

        if ($user) {

            if ($action == 'approve') {

                // Activate user
                $user->suspended = 0;

            } elseif ($action == 'reject') {

                // Suspend user
                $user->suspended = 1;
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