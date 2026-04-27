<?php
require('../../config.php');

require_login();

// Page setup
$PAGE->set_url('/local/aerobay_dashboard/index.php');
$PAGE->set_title('Aerobay Dashboard');
$PAGE->set_heading('Aerobay Dashboard');

echo $OUTPUT->header();

// Popup (Welcome message)
echo "<script>
    window.onload = function() {
        alert('Welcome to Aerobay Dashboard 🎉');
    };
</script>";

// Page content
echo "<h2>Welcome to Aerobay Dashboard</h2>";
echo "<p>This is your custom plugin page.</p>";

echo $OUTPUT->footer();