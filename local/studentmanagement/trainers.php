<?php
require('../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

require_login();

global $DB, $OUTPUT, $PAGE, $USER;

$context = context_system::instance();
$canmanage = is_siteadmin() || has_capability('local/studentmanagement:view', $context);

$istrainer = $DB->record_exists_sql("
    SELECT 1
      FROM {role_assignments} ra
      JOIN {role} r ON r.id = ra.roleid
     WHERE ra.userid = :userid
       AND ra.contextid = :contextid
       AND r.shortname = 'trainer'
", ['userid' => $USER->id, 'contextid' => $context->id]);

if (!$canmanage && !$istrainer) {
    redirect('/', 'Access denied');
}

$deleteid = optional_param('deleteid', 0, PARAM_INT);
if ($deleteid && $canmanage) {
    require_sesskey();

    if ($deleteid == $USER->id) {
        redirect(new moodle_url('/local/studentmanagement/trainers.php'), 'You cannot delete your own account.');
    }

    $trainer = $DB->get_record('user', ['id' => $deleteid, 'deleted' => 0], '*', MUST_EXIST);
    $hastrainerrole = $DB->record_exists_sql("
        SELECT 1
          FROM {role_assignments} ra
          JOIN {role} r ON r.id = ra.roleid
         WHERE ra.userid = :userid
           AND ra.contextid = :contextid
           AND r.shortname = 'trainer'
    ", ['userid' => $deleteid, 'contextid' => $context->id]);

    if ($hastrainerrole) {
        $DB->delete_records('trainer_school_map', ['trainerid' => $deleteid]);
        delete_user($trainer);
    }

    redirect(new moodle_url('/local/studentmanagement/trainers.php'), 'Trainer deleted successfully.');
}

$PAGE->set_url('/local/studentmanagement/trainers.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($canmanage ? 'Trainer Management' : 'My Trainer Assignments');
$PAGE->set_heading($canmanage ? 'Trainer Management' : 'My Trainer Assignments');

if ($canmanage) {
    $trainers = $DB->get_records_sql("
        SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.phone1
          FROM {user} u
          JOIN {role_assignments} ra ON ra.userid = u.id
          JOIN {role} r ON r.id = ra.roleid
         WHERE r.shortname = 'trainer'
           AND ra.contextid = :contextid
           AND u.deleted = 0
      ORDER BY u.firstname, u.lastname
    ", ['contextid' => $context->id]);
} else {
    $trainers = $DB->get_records('user', ['id' => $USER->id, 'deleted' => 0]);
}

$trainerids = array_keys($trainers);
$schoolsbytrainer = [];
$coursesbytrainer = [];

if (!empty($trainerids)) {
    list($insql, $params) = $DB->get_in_or_equal($trainerids, SQL_PARAMS_NAMED, 'tid');

    $schoolrecords = $DB->get_records_sql("
        SELECT tsm.id, tsm.trainerid, s.name
          FROM {trainer_school_map} tsm
          JOIN {school} s ON s.id = tsm.schoolid
         WHERE tsm.trainerid $insql
      ORDER BY s.name
    ", $params);

    foreach ($schoolrecords as $record) {
        $schoolsbytrainer[$record->trainerid][] = $record->name;
    }

    $courserecords = $DB->get_records_sql("
        SELECT DISTINCT CONCAT(tsm.trainerid, '-', c.id) AS id, tsm.trainerid, c.fullname
          FROM {trainer_school_map} tsm
          JOIN {school_course_map} scm ON scm.schoolid = tsm.schoolid
          JOIN {course} c ON c.id = scm.courseid
         WHERE tsm.trainerid $insql
      ORDER BY c.fullname
    ", $params);

    foreach ($courserecords as $record) {
        $coursesbytrainer[$record->trainerid][] = $record->fullname;
    }
}

echo $OUTPUT->header();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><?php echo $canmanage ? 'Trainer Management' : 'My Trainer Assignments'; ?></h3>
        <?php if ($canmanage) { ?>
            <div class="d-flex gap-2">
                <a href="add_trainer.php" class="btn btn-primary">Add Trainer</a>
                <a href="map_trainer_school.php" class="btn btn-success">Map School</a>
            </div>
        <?php } ?>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Assigned Schools</th>
                <th>Assigned Courses</th>
                <?php if ($canmanage) { ?>
                    <th class="text-center">Actions</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($trainers)) { ?>
                <?php foreach ($trainers as $trainer) { ?>
                    <tr>
                        <td><?php echo s(fullname($trainer)); ?></td>
                        <td><?php echo s($trainer->email); ?></td>
                        <td>
                            <?php echo !empty($schoolsbytrainer[$trainer->id])
                                ? s(implode(', ', $schoolsbytrainer[$trainer->id]))
                                : '<span class="text-danger">Not Assigned</span>'; ?>
                        </td>
                        <td>
                            <?php echo !empty($coursesbytrainer[$trainer->id])
                                ? s(implode(', ', $coursesbytrainer[$trainer->id]))
                                : '<span class="text-danger">Not Assigned</span>'; ?>
                        </td>
                        <?php if ($canmanage) { ?>
                            <td class="text-center">
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    <a href="add_trainer.php?id=<?php echo $trainer->id; ?>" class="btn btn-info btn-sm">Edit</a>
                                    <a href="map_trainer_school.php?trainerid=<?php echo $trainer->id; ?>" class="btn btn-success btn-sm">Map School</a>
                                    <a href="trainers.php?deleteid=<?php echo $trainer->id; ?>&sesskey=<?php echo sesskey(); ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this trainer?')">Delete</a>
                                </div>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="<?php echo $canmanage ? 5 : 4; ?>" class="text-center">No trainers found.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php
echo $OUTPUT->footer();
