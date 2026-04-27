<?php
require('../../config.php');
require_login();

$PAGE->set_url('/local/feedback/index.php');
$PAGE->set_title('Feedback');
$PAGE->set_heading('Feedback');

echo $OUTPUT->header();

global $DB, $USER;

// ROLE LOGIC
$isadmin = is_siteadmin();
$isteacher = false;
$isstudent = true;

// TEACHER LIST
$teachers = ['teacher1', 'teacher2', 'teacher3'];

if (in_array($USER->username, $teachers)) {
    $isteacher = true;
    $isstudent = false;
}

// ADMIN OVERRIDE
if ($isadmin) {
    $isteacher = false;
    $isstudent = false;
}

// Success message
$success = false;

// FORM SUBMIT (FIXED)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $record = new stdClass();

    $record->name = optional_param('name', '', PARAM_TEXT);
    $record->message = optional_param('message', '', PARAM_TEXT);

    // Email DB me hai to empty set kar diya
    $record->email = '';

    $record->timecreated = time();

    $DB->insert_record('local_feedback', $record);

    $success = true;
}
?>

<style>
.feedback-box {
    width: 420px;
    margin: 40px auto;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0,0,0,0.15);
    text-align: center;
    background: #ffffff;
}

.feedback-box input,
.feedback-box textarea {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-bottom: 15px;
}

.feedback-box button {
    background: #00eaff;
    color: #000;
    padding: 10px 20px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
}

.feedback-box button:hover {
    background: #00c3cc;
}

.success-msg {
    color: green;
    font-weight: bold;
    margin-bottom: 15px;
}
</style>

<!-- ROLE BASED UI -->

<?php if ($isstudent): ?>
<h3 style="text-align:center;"> Student Panel</h3>
<p style="text-align:center;">Submit your feedback below</p>
<?php endif; ?>

<?php if ($isteacher && !$isadmin): ?>
<h3 style="text-align:center;"> Teacher Panel</h3>
<p style="text-align:center;">View student feedback below</p>
<?php endif; ?>

<?php if ($isadmin): ?>
<h3 style="text-align:center;"> Admin Panel</h3>
<p style="text-align:center;">Full access to feedback system</p>
<?php endif; ?>

<!-- STUDENT FORM -->

<?php if ($isstudent): ?>
<div class="feedback-box">

<h2> Feedback Form</h2>

<?php if ($success): ?>
    <p class="success-msg">Thank you for your feedback!</p>
<?php endif; ?>

<form method="post" action="">

<input type="text" name="name" placeholder="Your Name" required>

<textarea name="message" placeholder="Write your feedback..." required></textarea>

<button type="submit">Submit</button>

</form>

</div>
<?php endif; ?>

<!-- TEACHER + ADMIN VIEW -->

<?php if ($isteacher || $isadmin): ?>

<h3 style="text-align:center;"> Feedback List</h3>

<?php
$records = $DB->get_records('local_feedback');

if ($records) {
    echo "<table border='1' style='margin:auto;width:80%;text-align:center;'>
    <tr>
        <th>Name</th>
        <th>Message</th>
        <th>Time</th>
    </tr>";

    foreach ($records as $r) {
        echo "<tr>
            <td>{$r->name}</td>
            <td>{$r->message}</td>
            <td>".date('d-m-Y H:i', $r->timecreated)."</td>
        </tr>";
    }

    echo "</table>";
} else {
    echo "<p style='text-align:center;'>No feedback available</p>";
}
?>

<?php endif; ?>

<?php echo $OUTPUT->footer(); ?>