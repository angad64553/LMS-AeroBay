<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add Student Management link in course navigation
 */
function local_studentmanagement_extend_navigation_course($navigation, $course, $context) {

    // Sirf course pages pe chale
    if ($course->id == SITEID) {
        return;
    }

    // Permission check (teacher/admin only)
    if (!has_capability('moodle/course:viewparticipants', $context)) {
        return;
    }

    // URL create
    $url = new moodle_url('/local/studentmanagement/index.php', [
        'id' => $course->id
    ]);

    // Navigation me add karo
    $navigation->add(
        'Student Management',
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'studentmanagement',
        new pix_icon('i/report', '')
    );
}