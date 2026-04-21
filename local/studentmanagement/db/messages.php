<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'notify' => [
        'capability'  => 'moodle/course:view',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED
        ],
    ],
];