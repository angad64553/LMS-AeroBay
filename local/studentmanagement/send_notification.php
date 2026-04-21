<?php
require('../../config.php');
require_login();

global $DB;

$userids = $_POST['users'] ?? [];

foreach ($userids as $userid) {

    $user = $DB->get_record('user', ['id' => $userid]);

    // SIMPLE DEBUG
    echo "Notification sent to: " . $user->email . "<br>";

    // yaha baad me Moodle message API use karenge
}

die;