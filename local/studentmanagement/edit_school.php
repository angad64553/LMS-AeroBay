<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

// GET ID
$id = required_param('id', PARAM_INT);

// FETCH RECORD
$school = $DB->get_record('school', ['id' => $id]);

if (!$school) {
    print_error('School not found');
}

// PAGE SETUP
$PAGE->set_url('/local/studentmanagement/edit_school.php', ['id' => $id]);
$PAGE->set_title('Edit School');
$PAGE->set_heading('Edit School');

echo $OUTPUT->header();

// UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $school->name = required_param('schoolname', PARAM_TEXT);
    $school->principalname = required_param('principalname', PARAM_TEXT);
    $school->email = required_param('email', PARAM_EMAIL);
    $school->phone = required_param('phone', PARAM_TEXT);
    $school->region = required_param('region', PARAM_TEXT);
    $school->address = required_param('address', PARAM_TEXT);

    $DB->update_record('school', $school);

    redirect(new moodle_url('/local/studentmanagement/schools.php'), 'School Updated Successfully');
}
?>

<div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-4">
        
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">✏️ Edit School</h4>
        </div>

        <div class="card-body p-4">

            <form method="post">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">School Name</label>
                        <input type="text" name="schoolname" class="form-control" 
                            value="<?php echo $school->name; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Principal Name</label>
                        <input type="text" name="principalname" class="form-control" 
                            value="<?php echo $school->principalname; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                            value="<?php echo $school->email; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" 
                            value="<?php echo $school->phone; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control" 
                            value="<?php echo $school->region; ?>">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo $school->address; ?></textarea>
                    </div>

                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="schools.php" class="btn btn-secondary">⬅️ Back</a>
                    <button type="submit" class="btn btn-success px-4">💾 Update</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();