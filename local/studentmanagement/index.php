<?php
require('../../config.php');

$courseid = required_param('id', PARAM_INT);

require_login($courseid);

global $DB, $PAGE, $OUTPUT;

// Page setup
$PAGE->set_url('/local/studentmanagement/index.php', ['id' => $courseid]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Student Management');

echo $OUTPUT->header();

// ✅ USERS FETCH (course-wise enrolled users)
$users = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, u.email
    FROM {user} u
    JOIN {user_enrolments} ue ON ue.userid = u.id
    JOIN {enrol} e ON e.id = ue.enrolid
    WHERE e.courseid = ?
", [$courseid]);
?>

<h3 style="margin-bottom:20px;">Student Management</h3>

<form method="post" action="bulk_action.php?id=<?php echo $courseid; ?>">

<table class="table table-bordered table-hover">
    <thead style="background:#2c3e50; color:white;">
        <tr>
            <th>
                <input type="checkbox" id="selectall">
            </th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th style="text-align:center;">Action</th>
        </tr>
    </thead>

    <tbody>

    <?php if (!empty($users)) { ?>
        <?php foreach ($users as $user) { ?>
            <tr>

                <td>
                    <input type="checkbox" name="userid[]" value="<?php echo $user->id; ?>">
                </td>

                <td><?php echo $user->firstname . ' ' . $user->lastname; ?></td>

                <td><?php echo $user->email; ?></td>

                <td>
                    <span style="color:orange; font-weight:bold;">Pending</span>
                </td>

                <td style="text-align:center;">

                    <a href="approve.php?id=<?php echo $user->id; ?>&courseid=<?php echo $courseid; ?>"
                       class="btn btn-success btn-sm"
                       style="margin-right:5px;">
                       Approve
                    </a>

                    <a href="reject.php?id=<?php echo $user->id; ?>&courseid=<?php echo $courseid; ?>"
                       class="btn btn-danger btn-sm">
                       Reject
                    </a>

                </td>

            </tr>
        <?php } ?>
    <?php } else { ?>

        <tr>
            <td colspan="5" style="text-align:center; padding:20px;">
                No students found
            </td>
        </tr>

    <?php } ?>

    </tbody>
</table>

<br>

<button type="submit" name="action" value="approve" class="btn btn-success">
    Bulk Approve
</button>

<button type="submit" name="action" value="reject" class="btn btn-danger">
    Bulk Reject
</button>

</form>

<script>
document.getElementById('selectall').onclick = function() {
    let checkboxes = document.querySelectorAll('input[name="userid[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
};
</script>

<?php
echo $OUTPUT->footer();
?>