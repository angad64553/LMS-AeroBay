<?php
require('../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$schoolid = required_param('schoolid', PARAM_INT);

$context = context_system::instance();

// Access control
if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context)) {
    redirect('/', 'Access denied');
}

// Page setup
$PAGE->set_url('/local/studentmanagement/school_students.php', ['schoolid' => $schoolid]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('School Students');

echo $OUTPUT->header();

$isadmin = is_siteadmin();

//  RM restriction check
if (!$isadmin) {

    $exists = $DB->record_exists('rm_school_map', [
        'rmid' => $USER->id,
        'schoolid' => $schoolid
    ]);

    if (!$exists) {
        print_error('You are not allowed to view this school');
    }
}

//  Fetch students of this school + course
$students = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, u.email, u.suspended,
           g.name AS gradename,
           c.fullname AS coursename
    FROM {user} u
    JOIN {local_studentmanagement} sm ON sm.userid = u.id
    JOIN {grade} g ON g.id = sm.gradeid
    LEFT JOIN {school_course_map} scm 
        ON scm.schoolid = sm.schoolid AND scm.gradeid = sm.gradeid
    LEFT JOIN {course} c ON c.id = scm.courseid
    WHERE sm.schoolid = ?
    AND u.deleted = 0
    AND u.suspended <> 2
", [$schoolid]);

?>

<h3>Students of School</h3>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Grade</th>
            <th>Course</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>

    <?php if (!empty($students)) { ?>
        <?php foreach ($students as $s) { ?>
            <tr>
                <td><?php echo s($s->firstname . ' ' . $s->lastname); ?></td>
                <td><?php echo s($s->email); ?></td>
                <td><?php echo s($s->gradename); ?></td>
                <td><?php echo $s->coursename ? s($s->coursename) : 'Not Assigned'; ?></td>

                <td>
                    <?php if ($s->suspended == 0) { ?>
                        <span style="color:green;">Active</span>
                    <?php } elseif ($s->suspended == 2) { ?>
                        <span style="color:red;">Rejected</span>
                    <?php } else { ?>
                        <span style="color:orange;">Pending</span>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="5">No students found</td>
        </tr>
    <?php } ?>

    </tbody>
</table>

<?php
echo $OUTPUT->footer();
?>
