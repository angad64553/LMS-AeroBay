<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Disable course-based navigation for Student Management
 * Now using global navigation instead
 */
function local_studentmanagement_extend_navigation_course($navigation, $course, $context) {
    // Do nothing (disabled)
}
