<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add Student Management link in course navigation
 */
function local_studentmanagement_extend_navigation_course($navigation, $course, $context) {

    // Site home pe show nahi kare
    if ($course->id == SITEID) {
        return;
    }

    // TEMP: capability hata do (debug ke liye)
    // if (!has_capability('moodle/course:viewparticipants', $context)) {
    //     return;
    // }

    // URL
    $url = new moodle_url('/local/studentmanagement/index.php', [
        'id' => $course->id
    ]);

    // Add in navigation
    $navigation->add(
        'Student Management',
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'studentmanagement',
        new pix_icon('i/report', '')
    );
}