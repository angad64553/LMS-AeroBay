<?php
namespace local_aerobay_dashboard\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;

class provider implements null_provider {

    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}