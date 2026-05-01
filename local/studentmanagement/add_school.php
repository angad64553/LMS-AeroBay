<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

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

    if ($id) {
        // UPDATE
        $record->id = $id;
        $DB->update_record('school', $record);
        redirect(new moodle_url('/local/studentmanagement/schools.php'), 'School Updated Successfully');
    } else {
        // INSERT
        $DB->insert_record('school', $record);
        redirect(new moodle_url('/local/studentmanagement/schools.php'), 'School Added Successfully');
    }
}
?>

<div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white rounded-top-4">
            <h4 class="mb-0">
                <?php echo $editmode ? 'Edit School' : 'Add New School'; ?>
            </h4>
        </div>

        <div class="card-body p-4">

            <form method="post">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">School Name</label>
                        <input type="text" name="schoolname" class="form-control"
                            placeholder="Enter school name"
                            value="<?php echo $school->name ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Principal Name</label>
                        <input type="text" name="principalname" class="form-control"
                            placeholder="Enter principal name"
                            value="<?php echo $school->principalname ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                            placeholder="Enter email"
                            value="<?php echo $school->email ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="phone" class="form-control"
                            placeholder="Enter phone number"
                            value="<?php echo $school->phone ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control"
                            placeholder="Enter region"
                            value="<?php echo $school->region ?? ''; ?>" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"
                            placeholder="Enter address" required><?php echo $school->address ?? ''; ?></textarea>
                    </div>

                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="schools.php" class="btn btn-secondary"> Back</a>
                    <button type="submit" class="btn btn-success px-4">
                        <?php echo $editmode ? 'Update School' : 'Save School'; ?>
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();