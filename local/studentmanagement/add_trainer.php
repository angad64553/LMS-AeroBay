<?php
require('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

require_login();

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

$context = context_system::instance();

if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context)) {
    redirect('/', 'Access denied');
}

$id = optional_param('id', 0, PARAM_INT);
$editmode = !empty($id);
$trainer = null;

if ($editmode) {
    $trainer = $DB->get_record('user', ['id' => $id, 'deleted' => 0], '*', MUST_EXIST);
    $hastrainerrole = $DB->record_exists_sql("
        SELECT 1
          FROM {role_assignments} ra
          JOIN {role} r ON r.id = ra.roleid
         WHERE ra.userid = :userid
           AND ra.contextid = :contextid
           AND r.shortname = 'trainer'
    ", ['userid' => $id, 'contextid' => $context->id]);

    if (!$hastrainerrole) {
        redirect(new moodle_url('/local/studentmanagement/trainers.php'), 'Invalid trainer selected.');
    }
}

$PAGE->set_url('/local/studentmanagement/add_trainer.php', ['id' => $id]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($editmode ? 'Edit Trainer' : 'Add Trainer');
$PAGE->set_heading($editmode ? 'Edit Trainer' : 'Add Trainer');

$error = '';
$columns = $DB->get_columns('trainer_school_map');
$hasschoolmapgrade = array_key_exists('gradeid', $columns);

if (is_siteadmin()) {
    $schools = $DB->get_records('school', null, 'name ASC');
} else {
    $schools = $DB->get_records_sql("
        SELECT s.*
          FROM {school} s
          JOIN {rm_school_map} rm ON rm.schoolid = s.id
         WHERE rm.rmid = :rmid
           AND rm.status = 1
      ORDER BY s.name
    ", ['rmid' => $USER->id]);
}

$selectedschools = [];
if ($editmode) {
    $schoolmaps = $DB->get_records('trainer_school_map', ['trainerid' => $id]);
    foreach ($schoolmaps as $schoolmap) {
        $selectedschools[] = $schoolmap->schoolid;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $name = trim(required_param('name', PARAM_TEXT));
    $email = trim(required_param('email', PARAM_EMAIL));
    $phone = trim(required_param('phone', PARAM_TEXT));
    $selectedschools = optional_param_array('schoolids', [], PARAM_INT);
    $selectedschools = array_values(array_unique($selectedschools));

    if ($name === '') {
        $error = 'Trainer name is required.';
    } else if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error = 'Enter a valid phone number.';
    } else if ($DB->record_exists_select('user', 'email = :email AND deleted = 0 AND id <> :id',
            ['email' => $email, 'id' => $id])) {
        $error = 'Email already registered.';
    } else {
        foreach ($selectedschools as $schoolid) {
            if (empty($schools[$schoolid])) {
                $error = 'Select valid schools only.';
                break;
            }
        }
    }

    if ($error === '') {
        $nameparts = preg_split('/\s+/', $name, 2);
        $firstname = $nameparts[0];
        $lastname = $nameparts[1] ?? 'Trainer';

        if ($editmode) {
            $user = new stdClass();
            $user->id = $id;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->email = $email;
            $user->phone1 = $phone;
            user_update_user($user, false);

            $DB->delete_records('trainer_school_map', ['trainerid' => $id]);
            foreach ($selectedschools as $schoolid) {
                $map = new stdClass();
                $map->trainerid = $id;
                $map->schoolid = $schoolid;
                if ($hasschoolmapgrade) {
                    $map->gradeid = 0;
                }
                $DB->insert_record('trainer_school_map', $map);
            }

            redirect(new moodle_url('/local/studentmanagement/trainers.php'), 'Trainer updated successfully.');
        }

        $user = new stdClass();
        $user->username = core_text::strtolower('trainer' . time());
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $email;
        $user->phone1 = $phone;
        $user->auth = 'manual';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1;
        $user->suspended = 0;
        $user->forcepasswordchange = 1;

        $trainerid = user_create_user($user, false);
        $createduser = $DB->get_record('user', ['id' => $trainerid], '*', MUST_EXIST);
        $newpassword = generate_password();
        update_internal_user_password($createduser, $newpassword);

        $role = $DB->get_record('role', ['shortname' => 'trainer']);
        if (!$role) {
            $roleid = create_role('Trainer', 'trainer', 'Trainer role for school and course delivery.');
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        } else {
            $roleid = $role->id;
        }

        if (!$DB->record_exists('role_assignments',
                ['roleid' => $roleid, 'contextid' => $context->id, 'userid' => $trainerid])) {
            role_assign($roleid, $trainerid, $context->id);
        }

        foreach ($selectedschools as $schoolid) {
            $map = new stdClass();
            $map->trainerid = $trainerid;
            $map->schoolid = $schoolid;
            if ($hasschoolmapgrade) {
                $map->gradeid = 0;
            }
            $DB->insert_record('trainer_school_map', $map);
        }

        $supportuser = core_user::get_support_user();
        $loginurl = $CFG->wwwroot . '/login/index.php';
        $subject = 'Your trainer account has been created';
        $message = "Hello " . fullname($createduser) . ",\n\n";
        $message .= "Your trainer account has been created.\n\n";
        $message .= "Login URL: " . $loginurl . "\n";
        $message .= "Login ID: " . $createduser->username . "\n";
        $message .= "Password: " . $newpassword . "\n\n";
        $message .= "Please change your password after first login.\n";

        $messagesent = email_to_user($createduser, $supportuser, $subject, $message);
        $redirectmessage = $messagesent
            ? 'Trainer created successfully. Login ID and password email has been sent to the trainer.'
            : 'Trainer created successfully, but the login email could not be sent. Please check Moodle email settings.';

        redirect(new moodle_url('/local/studentmanagement/trainers.php'),
            $redirectmessage);
    }
}

echo $OUTPUT->header();
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?php echo $editmode ? 'Edit Trainer' : 'Add Trainer'; ?></h4>
        </div>
        <div class="card-body">
            <?php if ($error !== '') { ?>
                <div class="alert alert-danger"><?php echo s($error); ?></div>
            <?php } ?>

            <form method="post">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                        value="<?php echo $trainer ? s(fullname($trainer)) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                        value="<?php echo $trainer ? s($trainer->email) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                        value="<?php echo $trainer ? s($trainer->phone1) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Schools</label>
                    <div class="border rounded p-2" style="max-height:220px; overflow:auto;">
                        <?php foreach ($schools as $school) { ?>
                            <label class="d-block">
                                <input type="checkbox" name="schoolids[]"
                                    value="<?php echo $school->id; ?>"
                                    <?php echo in_array($school->id, $selectedschools) ? 'checked' : ''; ?>>
                                <?php echo s($school->name); ?>
                            </label>
                        <?php } ?>
                    </div>
                    <small class="form-text text-muted">Trainer courses will come automatically from the selected schools course mappings.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editmode ? 'Update Trainer' : 'Create Trainer'; ?>
                    </button>
                    <a href="trainers.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
