<?php
require('../../config.php');

global $DB, $PAGE, $OUTPUT, $CFG;

$PAGE->set_url('/local/studentmanagement/register.php');
$PAGE->set_pagelayout('login');
$PAGE->set_title('Student Registration');

$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);
$email = optional_param('email', '', PARAM_EMAIL);
$mobile = optional_param('mobile', '', PARAM_TEXT);
$selectedgrade = optional_param('gradeid', 0, PARAM_INT);
$loadgrades = optional_param('loadgrades', 0, PARAM_INT);

// FETCH SCHOOLS
$schools = $DB->get_records('school');

// SELECTED SCHOOL
$selectedschool = optional_param('schoolid', 0, PARAM_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedschool = required_param('schoolid', PARAM_INT);
}

// FETCH GRADES BASED ON SCHOOL + COURSE MAPPING
$grades = [];
if (!empty($selectedschool)) {
    $grades = $DB->get_records_sql("
        SELECT DISTINCT g.id, g.name
          FROM {grade} g
          JOIN {school_course_map} scm ON scm.gradeid = g.id
         WHERE scm.schoolid = :schoolid
      ORDER BY g.name
    ", ['schoolid' => $selectedschool]);
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$loadgrades) {

    require_once($CFG->dirroot.'/user/lib.php');

    $firstname = trim($firstname);
    $lastname  = trim($lastname);
    $email     = trim($email);
    $mobile    = trim($mobile);
    $schoolid  = required_param('schoolid', PARAM_INT);
    $gradeid   = required_param('gradeid', PARAM_INT);

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Enter valid 10 digit mobile number";
    }

    if ($DB->record_exists('user', ['email' => $email])) {
        $error = "Email already registered";
    }

    if (!$DB->record_exists('school_course_map', ['schoolid' => $schoolid, 'gradeid' => $gradeid])) {
        $error = "Selected grade is not assigned to any course for this school";
    }

    if (empty($error)) {

        $username = 'stu' . time();

        $user = new stdClass();
        $user->username  = $username;
        $user->firstname = $firstname;
        $user->lastname  = $lastname;
        $user->email     = $email;
        $user->phone1    = $mobile;
        $user->auth      = 'manual';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1;
        $user->suspended = 1;

        $userid = user_create_user($user);

        // SAVE MAPPING
        $record = new stdClass();
        $record->userid   = $userid;
        $record->schoolid = $schoolid;
        $record->gradeid  = $gradeid;

        $DB->insert_record('local_studentmanagement', $record);

        $success = true;
    }
}

echo $OUTPUT->header();
?>

<style>
.card-box {
    width: 420px;
    margin: 40px auto;
    padding: 30px;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.form-control {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
.btn-main {
    width: 100%;
    padding: 10px;
    background: linear-gradient(90deg,#00c6ff,#0072ff);
    color: #fff;
    border: none;
    border-radius: 25px;
}
.success-msg { color: green; text-align:center; }
.error-msg { color: red; text-align:center; }
</style>

<div class="card-box">

<h3>Student Registration</h3>

<?php if ($success): ?>
<p class="success-msg">Registration successful. Wait for approval.</p>
<?php endif; ?>

<?php if (!empty($error)): ?>
<p class="error-msg"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post" id="studentregistrationform">
<input type="hidden" name="loadgrades" id="loadgrades" value="0">

<input type="text" name="firstname" placeholder="First Name" required class="form-control" value="<?php echo s($firstname); ?>">
<input type="text" name="lastname" placeholder="Last Name" required class="form-control" value="<?php echo s($lastname); ?>">
<input type="email" name="email" placeholder="Email Address" required class="form-control" value="<?php echo s($email); ?>">
<input type="text" name="mobile" placeholder="Mobile Number" required class="form-control" value="<?php echo s($mobile); ?>">

<select name="schoolid" id="schoolid" class="form-control" required>
<option value="">Select School</option>
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s->id; ?>" <?php echo ($selectedschool == $s->id) ? 'selected' : ''; ?>>
<?php echo $s->name; ?>
</option>
<?php } ?>
</select>

<select name="gradeid" class="form-control" required>
<option value="">Select Grade</option>
<?php foreach ($grades as $g) { ?>
<option value="<?php echo $g->id; ?>" <?php echo ($selectedgrade == $g->id) ? 'selected' : ''; ?>>
<?php echo $g->name; ?>
</option>
<?php } ?>
</select>

<?php if (empty($selectedschool)): ?>
<p style="color:#666; font-size:13px; margin-top:-6px;">Select a school first to load available grades.</p>
<?php elseif (empty($grades)): ?>
<p style="color:red; font-size:13px; margin-top:-6px;">No grades are mapped to courses for this school.</p>
<?php endif; ?>

<button type="submit" class="btn-main">Register</button>

</form>

</div>

<script>
document.getElementById('schoolid').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('loadgrades').value = '1';
        document.getElementById('studentregistrationform').submit();
    }
});
</script>

<?php echo $OUTPUT->footer(); ?>
