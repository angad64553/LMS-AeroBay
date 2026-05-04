<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$id = optional_param('id', 0, PARAM_INT);
$editmode = false;

// EDIT MODE
if ($id) {
    $school = $DB->get_record('school', ['id' => $id], '*', MUST_EXIST);
    $editmode = true;
} else {
    $school = new stdClass();
}

// PAGE SETUP
$PAGE->set_url('/local/studentmanagement/add_school.php', ['id' => $id]);
$PAGE->set_title($editmode ? 'Edit School' : 'Add School');
$PAGE->set_heading($editmode ? 'Edit School' : 'Add School');

echo $OUTPUT->header();

// GET GRADES
$allgrades = $DB->get_records('grade');

// GET RM USERS (role based bhi kar sakte ho future me)
$rms = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    JOIN {role} r ON r.id = ra.roleid
    WHERE r.shortname = 'rm'
    AND u.deleted = 0
");
// SELECTED GRADES
$selectedgrades = [];
$selectedrm = 0;

if ($editmode) {

    // grade mapping
    $maps = $DB->get_records('school_grade_map', ['schoolid' => $id]);
    foreach ($maps as $m) {
        $selectedgrades[] = $m->gradeid;
    }

    // rm mapping
    $rmmap = $DB->get_record('rm_school_map', ['schoolid' => $id]);
    if ($rmmap) {
        $selectedrm = $rmmap->rmid;
    }
}

// FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $record = new stdClass();
    $record->name = required_param('schoolname', PARAM_TEXT);
    $record->address = required_param('address', PARAM_TEXT);
    $record->principalname = required_param('principalname', PARAM_TEXT);
    $record->email = required_param('email', PARAM_EMAIL);
    $record->phone = required_param('phone', PARAM_TEXT);
    $record->region = required_param('region', PARAM_TEXT);
    $record->status = 1;

    $grades = optional_param_array('grades', [], PARAM_INT);
    $rmid   = required_param('rmid', PARAM_INT);

    if ($id) {
        // UPDATE SCHOOL
        $record->id = $id;
        $DB->update_record('school', $record);

        // DELETE OLD GRADE MAP
        $DB->delete_records('school_grade_map', ['schoolid' => $id]);

        // DELETE OLD RM MAP
        $DB->delete_records('rm_school_map', ['schoolid' => $id]);

        $schoolid = $id;

    } else {
        // INSERT SCHOOL
        $schoolid = $DB->insert_record('school', $record);
    }

    // INSERT GRADE MAP
    foreach ($grades as $gid) {
        $map = new stdClass();
        $map->schoolid = $schoolid;
        $map->gradeid = $gid;
        $DB->insert_record('school_grade_map', $map);
    }

    // INSERT RM MAP (Pending)
    $rmmap = new stdClass();
    $rmmap->schoolid = $schoolid;
    $rmmap->rmid = $rmid;
    $rmmap->status = 0; // pending
    $DB->insert_record('rm_school_map', $rmmap);

    // SEND NOTIFICATION TO RM
    require_once($CFG->dirroot . '/message/lib.php');

    $message = new \core\message\message();

    $message->component = 'local_studentmanagement';
    $message->name = 'school_assignment';
    $message->userfrom = $USER;
    $message->userto = $rmid;

    $message->subject = 'New School Assigned';
    $message->fullmessage = 'You have been assigned a new school. Please login and accept or reject.';
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '<p>You have been assigned a new school.</p><p>Please login and accept or reject.</p>';
    $message->smallmessage = 'New school assigned';

    $message->contexturl = $CFG->wwwroot . '/local/studentmanagement/schools.php';
    $message->contexturlname = 'View Schools';

    $message->notification = 1;

    message_send($message);

    redirect(new moodle_url('/local/studentmanagement/schools.php'),
        $editmode ? 'School Updated Successfully' : 'School Added Successfully');
}
?>

<div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-4">

        <div class="card-header bg-primary text-white">
            <h4><?php echo $editmode ? 'Edit School' : 'Add School'; ?></h4>
        </div>

        <div class="card-body">

            <form method="post">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label>School Name</label>
                        <input type="text" name="schoolname" class="form-control"
                        value="<?php echo $school->name ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Principal Name</label>
                        <input type="text" name="principalname" class="form-control"
                        value="<?php echo $school->principalname ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                        value="<?php echo $school->email ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                        value="<?php echo $school->phone ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Region</label>
                        <input type="text" name="region" class="form-control"
                        value="<?php echo $school->region ?? ''; ?>" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" required><?php echo $school->address ?? ''; ?></textarea>
                    </div>

                    <!-- RM SELECT -->
                    <div class="col-md-6 mb-3">
                        <label>Select RM</label>
                        <select name="rmid" class="form-control" required>
                            <option value="">Select RM</option>
                            <?php foreach ($rms as $rm) { ?>
                                <option value="<?php echo $rm->id; ?>"
                                    <?php echo ($selectedrm == $rm->id) ? 'selected' : ''; ?>>
                                    <?php echo $rm->firstname . ' ' . $rm->lastname; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- GRADES -->
                    <div class="col-md-12 mb-3">
                        <label>Select Grades</label>
                        <div style="border:1px solid #ccc; padding:10px;">
                            <?php foreach ($allgrades as $g) { ?>
                                <div>
                                    <input type="checkbox" name="grades[]"
                                    value="<?php echo $g->id; ?>"
                                    <?php echo in_array($g->id, $selectedgrades) ? 'checked' : ''; ?>>
                                    Grade <?php echo $g->name; ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="schools.php" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-success">
                        <?php echo $editmode ? 'Update' : 'Save'; ?>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php echo $OUTPUT->footer(); ?>