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

// RM ke school fetch
if ($isadmin) {
    $schools = $DB->get_records('school');
} else {
    $records = $DB->get_records('rm_school_map', ['rmid' => $USER->id]);

    $schoolids = [];
    foreach ($records as $r) {
        $schoolids[] = $r->schoolid;
    }

    if (!empty($schoolids)) {
        list($in_sql, $params) = $DB->get_in_or_equal($schoolids);

        $schools = $DB->get_records_sql("
            SELECT * FROM {school}
            WHERE id $in_sql
        ", $params);
    } else {
        $schools = [];
    }
}
?>

<h3 style="margin-bottom:20px;">My Schools</h3>
<div style="margin-bottom: 15px;">
    <a href="add_school.php" class="btn btn-primary">Add School</a>
    <a href="export_csv.php" class="btn btn-success">Export CSV</a>
</div>
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
                <td><?php echo $s->name; ?></td>
                <td>
                    <a href="index.php?schoolid=<?php echo $s->id; ?>"
                       class="btn btn-primary btn-sm">
                       View Students
                    </a>
             <a href="add_school.php?id=<?php echo $s->id; ?>" class="btn btn-primary btn-sm">
    Edit
</a>|
    <a href="delete_school.php?id=<?php echo $school->id; ?>" class="btn btn-primary btn-sm">Delete</a> |
    <a href="suspend_school.php?id=<?php echo $school->id; ?>" class="btn btn-primary btn-sm">
        <?php echo $school->status ? 'Suspend' : 'Suspend'; ?>
    </a> |
    <a href="view_school.php?id=<?php echo $s->id; ?>" class="btn btn-primary btn-sm">View</a>
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