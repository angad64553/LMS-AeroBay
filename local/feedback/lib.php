<?php
defined('MOODLE_INTERNAL') || die();

function local_feedback_extend_navigation(global_navigation $nav) {
    global $USER;

    if (isloggedin() && !isguestuser()) {

        $url = new moodle_url('/local/feedback/index.php');

        $node = navigation_node::create(
            'Feedback',
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'feedback',
            new pix_icon('i/feedback', '')
        );

        // 🔥 IMPORTANT: Add near top (navbar area)
        $nav->add_node($node);
    }
}