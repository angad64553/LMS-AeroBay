<?php
require('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

// GET ID
$id = required_param('id', PARAM_INT);

// GET RECORD (SAFE WAY)
$school = $DB->get_record('school', ['id' => $id]);

// PAGE SETUP
$PAGE->set_url('/local/studentmanagement/view_school.php', ['id' => $id]);
$PAGE->set_title('View School');
$PAGE->set_heading('School Details');

echo $OUTPUT->header();

// IF NOT FOUND
if (!$school) {
    echo "<div class='container mt-4'>";
    echo "<div class='alert alert-danger'>❌ School not found</div>";
    echo "<a href='schools.php' class='btn btn-secondary'>⬅️ Back</a>";
    echo "</div>";
    echo $OUTPUT->footer();
    exit;
}
?>

<div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-4">
        
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"> School Details</h4>
        </div>

        <div class="card-body p-4">

            <p><strong>Name:</strong> <?php echo $school->name ?? '-'; ?></p>
            <p><strong>Principal:</strong> <?php echo $school->principalname ?? '-'; ?></p>
            <p><strong>Email:</strong> <?php echo $school->email ?? '-'; ?></p>
            <p><strong>Phone:</strong> <?php echo $school->phone ?? '-'; ?></p>
            <p><strong>Region:</strong> <?php echo $school->region ?? '-'; ?></p>
            <p><strong>Address:</strong> <?php echo $school->address ?? '-'; ?></p>

            <p><strong>Status:</strong> 
                <?php echo (!empty($school->status) && $school->status == 1) ? 'Active' : 'Suspended'; ?>
            </p>

            <a href="schools.php" class="btn btn-secondary mt-3"> Back</a>

        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();