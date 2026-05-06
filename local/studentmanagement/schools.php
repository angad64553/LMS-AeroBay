<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();

if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context)) {
    redirect('/', 'Access denied');
}

$PAGE->set_url('/local/studentmanagement/schools.php');
$PAGE->set_title('My Schools');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$isadmin = is_siteadmin();

// ================= ADMIN =================
if ($isadmin) {
    $schools = $DB->get_records('school');

// ================= RM =================
} else {

    $sql = "
        SELECT s.*, rm.status
        FROM {school} s
        JOIN {rm_school_map} rm ON rm.schoolid = s.id
        WHERE rm.rmid = :rmid
    ";

    $schools = $DB->get_records_sql($sql, ['rmid' => $USER->id]);
}
?>

<h3 style="margin-bottom:20px;">My Schools</h3>

<?php if ($isadmin) { ?>
<div style="margin-bottom: 15px;">
    <a href="add_school.php" class="btn btn-primary">Add School</a>
    <a href="export_csv.php" class="btn btn-success">Export CSV</a>
    <a href="grades.php" class="btn btn-info">Grades</a>
</div>
<?php } ?>

<table class="table table-bordered">
    <thead style="background:#2c3e50; color:white;">
        <tr>
            <th>School Name</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
    <?php if (!empty($schools)) { ?>
        <?php foreach ($schools as $s) { ?>
            <tr>

                <td><?php echo s($s->name); ?></td>

                <td>

                    <?php if ($isadmin) { ?>

                        <!-- ADMIN CONTROLS -->
                        <a href="index.php?schoolid=<?php echo $s->id; ?>" class="btn btn-primary btn-sm">View Students</a>

                        <a href="add_school.php?id=<?php echo $s->id; ?>" class="btn btn-info btn-sm">Edit</a>

                        <a href="delete_school.php?id=<?php echo $s->id; ?>" class="btn btn-danger btn-sm">Delete</a>

                        <a href="suspend_school.php?id=<?php echo $s->id; ?>" class="btn btn-warning btn-sm">
                            Suspend
                        </a>

                        <a href="view_school.php?id=<?php echo $s->id; ?>" class="btn btn-secondary btn-sm">View</a>

                    <?php } else { ?>

                        <!-- RM CONTROLS -->

                        <?php if ($s->status == 0) { ?>

                            <a href="accept.php?id=<?php echo $s->id; ?>&sesskey=<?php echo sesskey(); ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure to accept this school?')">
                                Accept
                            </a>

                            <a href="reject_school.php?id=<?php echo $s->id; ?>&sesskey=<?php echo sesskey(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to reject this school?')">
                                Reject
                            </a>

                        <?php } elseif ($s->status == 1) { ?>

                            <div style="display:flex; gap:6px; align-items:center; justify-content:center;">
                                <span style="color:green; font-weight:bold;">Accepted</span>

                                <a href="index.php?schoolid=<?php echo $s->id; ?>" class="btn btn-primary btn-sm">
                                    View Students
                                </a>

                                <a href="view_school.php?id=<?php echo $s->id; ?>" class="btn btn-secondary btn-sm">
                                    View
                                </a>
                            </div>

                        <?php } elseif ($s->status == 2) { ?>

                            <span style="color:red; font-weight:bold;">Rejected</span>

                        <?php } ?>

                    <?php } ?>

                </td>

            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="2" style="text-align:center;">No schools found</td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<?php
echo $OUTPUT->footer();
?>
