<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();
$istrainer = $DB->record_exists_sql("
    SELECT 1
      FROM {role_assignments} ra
      JOIN {role} r ON r.id = ra.roleid
     WHERE ra.userid = :userid
       AND ra.contextid = :contextid
       AND r.shortname = 'trainer'
", ['userid' => $USER->id, 'contextid' => $context->id]);

// Allow Admin, RM and trainers.
if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context) && !$istrainer) {
    redirect('/', 'Access denied');
}

// Page setup
$PAGE->set_url('/local/studentmanagement/index.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Student Management');

echo $OUTPUT->header();

$schoolfilter = optional_param('schoolid', 0, PARAM_INT);
$params = [];
$isadmin = is_siteadmin();
$canmanage = $isadmin || has_capability('local/studentmanagement:view', $context);

// ================= ADMIN =================
if ($isadmin) {

    $sql = "
    SELECT u.id, u.firstname, u.lastname, u.email, u.suspended,
           sm.schoolid, sm.gradeid,
           s.name AS schoolname,
           g.name AS gradename,
           c.fullname AS coursename
    FROM {user} u
    JOIN {local_studentmanagement} sm ON sm.userid = u.id
    JOIN {school} s ON s.id = sm.schoolid
    JOIN {grade} g ON g.id = sm.gradeid
    LEFT JOIN {school_course_map} scm 
        ON scm.schoolid = sm.schoolid AND scm.gradeid = sm.gradeid
    LEFT JOIN {course} c 
        ON c.id = scm.courseid
    WHERE u.deleted = 0
    AND u.suspended <> 2
    AND u.username NOT IN ('admin','guest')
    ";

    if ($schoolfilter) {
        $sql .= " AND sm.schoolid = :schoolid";
        $params['schoolid'] = $schoolfilter;
    }

    $students = $DB->get_records_sql($sql, $params);

// ================= RM =================
} else if ($canmanage) {

    $sql = "
    SELECT u.id, u.firstname, u.lastname, u.email, u.suspended,
           sm.schoolid, sm.gradeid,
           s.name AS schoolname,
           g.name AS gradename,
           c.fullname AS coursename
    FROM {user} u
    JOIN {local_studentmanagement} sm ON sm.userid = u.id
    JOIN {school} s ON s.id = sm.schoolid
    JOIN {grade} g ON g.id = sm.gradeid
    LEFT JOIN {school_course_map} scm 
        ON scm.schoolid = sm.schoolid AND scm.gradeid = sm.gradeid
    LEFT JOIN {course} c 
        ON c.id = scm.courseid
    JOIN {rm_school_map} rm ON rm.schoolid = sm.schoolid
    WHERE rm.rmid = :rmid
    AND rm.status = 1
    AND u.deleted = 0
    AND u.suspended <> 2
    AND u.username NOT IN ('admin','guest')
    ";

    $params['rmid'] = $USER->id;

    if ($schoolfilter) {
        $sql .= " AND sm.schoolid = :schoolid";
        $params['schoolid'] = $schoolfilter;
    }

    $students = $DB->get_records_sql($sql, $params);

// ================= TRAINER =================
} else {

    $sql = "
    SELECT u.id, u.firstname, u.lastname, u.email, u.suspended,
           sm.schoolid, sm.gradeid,
           s.name AS schoolname,
           g.name AS gradename,
           c.fullname AS coursename
    FROM {user} u
    JOIN {local_studentmanagement} sm ON sm.userid = u.id
    JOIN {school} s ON s.id = sm.schoolid
    JOIN {grade} g ON g.id = sm.gradeid
    JOIN {school_course_map} scm
        ON scm.schoolid = sm.schoolid AND scm.gradeid = sm.gradeid
    JOIN {course} c
        ON c.id = scm.courseid
    JOIN {trainer_school_map} tsm
        ON tsm.schoolid = sm.schoolid AND tsm.trainerid = :trainerid
    WHERE u.deleted = 0
    AND u.suspended <> 2
    AND u.username NOT IN ('admin','guest')
    ";

    $params['trainerid'] = $USER->id;

    if ($schoolfilter) {
        $sql .= " AND sm.schoolid = :schoolid";
        $params['schoolid'] = $schoolfilter;
    }

    $students = $DB->get_records_sql($sql, $params);
}
?>

<h3 style="margin-bottom:20px;">Student Management</h3>

<form method="post" action="bulk_action.php">
<input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

<table class="table table-bordered table-hover">
    <thead style="background:#2c3e50; color:white;">
        <tr>
            <?php if ($canmanage) { ?>
                <th><input type="checkbox" id="selectall"></th>
            <?php } ?>
            <th>Name</th>
            <th>Email</th>
            <th>School</th>
            <th>Grade</th>
            <th>Course</th>
            <th>Status</th>
            <?php if ($canmanage) { ?>
                <th style="text-align:center;">Action</th>
            <?php } ?>
        </tr>
    </thead>

    <tbody>

    <?php if (!empty($students)) { ?>
        <?php foreach ($students as $user) { ?>
            <tr>

                <?php if ($canmanage) { ?>
                    <td>
                        <input type="checkbox" name="userid[]" value="<?php echo $user->id; ?>">
                    </td>
                <?php } ?>

                <td><?php echo s($user->firstname . ' ' . $user->lastname); ?></td>

                <td><?php echo s($user->email); ?></td>

                <td><?php echo s($user->schoolname); ?></td>

                <td><?php echo s($user->gradename); ?></td>

                <td>
                    <?php echo $user->coursename ? s($user->coursename) : '<span style="color:red;">Not Assigned</span>'; ?>
                </td>

                <td>
                    <?php if ($user->suspended == 0) { ?>
                        <span style="color:green; font-weight:bold;">Active</span>
                    <?php } elseif ($user->suspended == 2) { ?>
                        <span style="color:red; font-weight:bold;">Rejected</span>
                    <?php } else { ?>
                        <span style="color:orange; font-weight:bold;">Pending</span>
                    <?php } ?>
                </td>

                <?php if ($canmanage) { ?>
                    <td style="text-align:center;">

                    <?php if ($user->suspended == 1) { ?>

                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="approve.php?id=<?php echo $user->id; ?>&sesskey=<?php echo sesskey(); ?>"
                            class="btn btn-success btn-sm">
                            Approve
                            </a>

                            <a href="reject.php?id=<?php echo $user->id; ?>&sesskey=<?php echo sesskey(); ?>"
                            class="btn btn-warning btn-sm">
                            Reject
                            </a>
                        </div>

                    <?php } else { ?>

                        <div style="display:flex; gap:6px; justify-content:center; align-items:center;">

                            <a href="delete.php?id=<?php echo $user->id; ?>&sesskey=<?php echo sesskey(); ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure to delete this user?')">
                            Delete
                            </a>
                        </div>

                    <?php } ?>

                    </td>
                <?php } ?>

            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="<?php echo $canmanage ? 8 : 6; ?>" style="text-align:center; padding:20px;">
                No students found
            </td>
        </tr>
    <?php } ?>

    </tbody>
</table>

<br>

<?php if ($canmanage) { ?>
    <button type="submit" name="action" value="approve" class="btn btn-success">
        Bulk Approve
    </button>

    <button type="submit" name="action" value="reject" class="btn btn-danger">
        Bulk Reject
    </button>
<?php } ?>

</form>

<?php if ($canmanage) { ?>
    <script>
    document.getElementById('selectall').onclick = function() {
        let checkboxes = document.querySelectorAll('input[name="userid[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    };
    </script>
<?php } ?>

<?php
echo $OUTPUT->footer();
?>
