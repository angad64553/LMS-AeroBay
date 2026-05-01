<?php
require('../../config.php');

global $DB, $PAGE, $OUTPUT, $CFG;

$PAGE->set_url('/local/studentmanagement/register.php');
$PAGE->set_pagelayout('login');
$PAGE->set_title('Student Registration');

echo $OUTPUT->header();

// Fetch data
$schools = $DB->get_records('school');
$grades  = $DB->get_records('grade');

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once($CFG->dirroot.'/user/lib.php');

    $firstname = trim(required_param('firstname', PARAM_TEXT));
    $lastname  = trim(required_param('lastname', PARAM_TEXT));
    $email     = trim(required_param('email', PARAM_EMAIL));
    $mobile    = trim(required_param('mobile', PARAM_TEXT));
    $schoolid  = required_param('schoolid', PARAM_INT);
    $gradeid   = required_param('gradeid', PARAM_INT);

    // 🔒 Mobile validation
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Enter valid 10 digit mobile number";
    }

    // 🔒 Email duplicate check
    if ($DB->record_exists('user', ['email' => $email])) {
        $error = "Email already registered";
    }

    if (empty($error)) {

        // 🔥 Generate UNIQUE username
        $username = 'stu' . time();

        // Create user
        $user = new stdClass();
        $user->username  = $username; // ✅ FIXED
        $user->firstname = $firstname;
        $user->lastname  = $lastname;
        $user->email     = $email;
        $user->phone1    = $mobile;
        $user->auth      = 'manual';
        $user->confirmed = 1;
        $user->suspended = 1;

        // Temporary password (later approve pe change hoga)
        $userid = user_create_user($user);

    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
update_internal_user_password($user, 'Temp@123');

        // Save mapping
        $record = new stdClass();
        $record->userid   = $userid;
        $record->schoolid = $schoolid;
        $record->gradeid  = $gradeid;

        $DB->insert_record('local_studentmanagement', $record);

        $success = true;
    }
}
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

.card-box h3 {
    text-align: center;
    margin-bottom: 20px;
    font-weight: 600;
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
    font-weight: 600;
}

.btn-main:hover {
    opacity: 0.9;
}

.success-msg {
    color: green;
    text-align: center;
    margin-bottom: 10px;
}

.error-msg {
    color: red;
    text-align: center;
    margin-bottom: 10px;
}
</style>

<div class="card-box">

<h3>Student Registration</h3>

<?php if ($success): ?>
    <p class="success-msg">Registration successful. Wait for approval.</p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <p class="error-msg"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post">

<input type="text" name="firstname" placeholder="First Name" required class="form-control">

<input type="text" name="lastname" placeholder="Last Name" required class="form-control">

<input type="email" name="email" placeholder="Email Address" required class="form-control">

<input type="text" name="mobile" placeholder="Mobile Number" required class="form-control">

<select name="schoolid" required class="form-control">
<option value="">Select School</option>
<?php foreach ($schools as $s) { ?>
<option value="<?php echo $s->id; ?>"><?php echo $s->name; ?></option>
<?php } ?>
</select>

<select name="gradeid" required class="form-control">
<option value="">Select Grade</option>
<?php foreach ($grades as $g) { ?>
<option value="<?php echo $g->id; ?>"><?php echo $g->name; ?></option>
<?php } ?>
</select>

<button type="submit" class="btn-main">Register</button>

</form>

</div>

<?php echo $OUTPUT->footer(); ?>



