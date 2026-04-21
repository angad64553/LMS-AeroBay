<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_url('/local/studentmanagement/report.php', ['courseid' => $courseid]);
$PAGE->set_title('Not Attempted Students');
$PAGE->set_heading('Not Attempted Students');

echo $OUTPUT->header();

// SQL
$sql = "SELECT u.id, u.firstname, u.lastname, u.email
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = ctx.id
        JOIN {role} r ON r.id = ra.roleid
        WHERE c.id = ?
        AND r.shortname = 'student'
        AND u.id NOT IN (SELECT qa.userid FROM {quiz_attempts} qa)
        AND u.deleted = 0";

$students = $DB->get_records_sql($sql, [$courseid]);

?>

<form method="post" action="send_notification.php">

<button type="button" onclick="toggleAll(true)">Select All</button>
<button type="button" onclick="toggleAll(false)">Unselect All</button>

<br><br>

<table border="1" cellpadding="10">
<tr>
    <th>Select</th>
    <th>Name</th>
    <th>Email</th>
</tr>

<?php foreach ($students as $s) { ?>
<tr>
    <td><input type="checkbox" name="users[]" value="<?php echo $s->id; ?>"></td>
    <td><?php echo $s->firstname . ' ' . $s->lastname; ?></td>
    <td><?php echo $s->email; ?></td>
</tr>
<?php } ?>

</table>

<br>

<button type="submit">Send Notification</button>

</form>

<script>
function toggleAll(state) {
    let checkboxes = document.querySelectorAll('input[type=checkbox]');
    checkboxes.forEach(cb => cb.checked = state);
}
</script>

<?php
echo $OUTPUT->footer();