<?php
defined('MOODLE_INTERNAL') || die();

function local_aerobay_dashboard_before_footer() {
    global $USER, $SESSION;

    if (isloggedin() && !isguestuser()) {

        // Admin + Teacher block
if (is_siteadmin() || user_has_role_assignment($USER->id, 3)) {
    return;
}

        //  Sirf ek baar popup
        if (!empty($SESSION->popup_shown)) {
            return;
        }

        $SESSION->popup_shown = true;

        $courses = enrol_get_users_courses($USER->id, true);
        $count = count($courses);
        $name = fullname($USER);

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {

            var name = ' . json_encode($name) . ';
            var count = ' . json_encode($count) . ';

            var popup = document.createElement("div");

            popup.innerHTML =
                "<div style=\"position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); text-align: center; z-index: 9999; width: 320px;\">" +

                "<h2>Welcome, " + name + " </h2>" +

                "<p>You are enrolled in <b>" + count + "</b> courses </p>" +

                "<button onclick=\"this.parentElement.remove()\" style=\"padding:10px 20px; border:none; background:#007bff; color:white; border-radius:8px;\">Continue</button>" +

                "</div>";

            document.body.appendChild(popup);
        });
        </script>';
    }
}