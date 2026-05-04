<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [

    'school_assignment' => [
        'capability'  => 'local/studentmanagement:view',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED
        ],
    ],

    'student_approved' => [
        'capability' => 'moodle/site:sendmessage',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED
        ],
    ],

];