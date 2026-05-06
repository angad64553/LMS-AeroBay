<?php
require('../../config.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/enrol/manual/locallib.php');
require_login();

global $DB, $OUTPUT, $PAGE, $USER;

$context = context_system::instance();

if (!is_siteadmin() && !has_capability('local/studentmanagement:view', $context)) {
    redirect('/', 'Access denied');
}

$selectedtrainer = optional_param('trainerid', 0, PARAM_INT);
$selectedschool = optional_param('schoolid', 0, PARAM_INT);
$error = '';
$columns = $DB->get_columns('trainer_school_map');

$hasschoolmapgrade = array_key_exists('gradeid', $columns);

$trainers = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
      FROM {user} u
      JOIN {role_assignments} ra ON ra.userid = u.id
      JOIN {role} r ON r.id = ra.roleid
     WHERE r.shortname = 'trainer'
       AND ra.contextid = :contextid
       AND u.deleted = 0
  ORDER BY u.firstname, u.lastname
", ['contextid' => $context->id]);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $trainerid = required_param('trainerid', PARAM_INT);
    $schoolid = required_param('schoolid', PARAM_INT);

    if (empty($trainers[$trainerid])) {
        $error = 'Select a valid trainer.';
    } else if (empty($schools[$schoolid])) {
        $error = 'Select a valid school.';
    } else {
        $record = new stdClass();

        $record->trainerid = $trainerid;
        $record->schoolid = $schoolid;

        if ($hasschoolmapgrade) {
            $record->gradeid = 0;
        }

        try {
            // Get trainer role.
            $trainerrole = $DB->get_record('role', ['shortname' => 'trainer']);

            if (!$trainerrole) {
                $trainerrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
            }

            // 1. Get old courses from current mappings. 
            $oldcourseids = [];
            $oldschoolmaps = $DB->get_records('trainer_school_map', ['trainerid' => $trainerid]);
            foreach ($oldschoolmaps as $oldmap) {
                $oldcourses = $DB->get_records('school_course_map', ['schoolid' => $oldmap->schoolid]);
                foreach ($oldcourses as $oc) {
                    if (!empty($oc->courseid)) {
                        $oldcourseids[$oc->courseid] = $oc->courseid;
                    }
                }
            }

            // 2. Get new courses for the new school.
            $newcourseids = [];
            $schoolcourses = $DB->get_records('school_course_map', ['schoolid' => $schoolid]);
            foreach ($schoolcourses as $sc) {
                if (!empty($sc->courseid)) {
                    $newcourseids[$sc->courseid] = $sc->courseid;
                }
            }

            // 3. Determine which courses to remove.
            $coursestoremove = array_diff($oldcourseids, $newcourseids);

            foreach ($coursestoremove as $courseid) {
                $coursecontext = context_course::instance($courseid);

                // Properly unassign the role to fix cache and capability issues.
                if ($trainerrole) {
                    role_unassign($trainerrole->id, $trainerid, $coursecontext->id);
                }

                // Remove all enrolments from the old course.
                $instances = enrol_get_instances($courseid, true);
                foreach ($instances as $instance) {
                    $plugin = enrol_get_plugin($instance->enrol);
                    if ($plugin) {
                        $plugin->unenrol_user($instance, $trainerid);
                    }
                }
            }

            // Delete previous mappings.
            $DB->delete_records('trainer_school_map', [
                'trainerid' => $trainerid
            ]);

            // Create new mapping.
            $DB->insert_record('trainer_school_map', $record);

            // 4. Assign new school courses (only new ones to avoid redundant enrolling).
            $coursestoadd = array_diff($newcourseids, $oldcourseids);

            foreach ($coursestoadd as $courseid) {
                if (!$trainerrole) {
                    continue;
                }

                $course = $DB->get_record('course', ['id' => $courseid]);
                if (!$course) {
                    continue;
                }

                // Get/create manual enrol instance.
                $instances = enrol_get_instances($course->id, true);
                $manualinstance = null;

                foreach ($instances as $instance) {
                    if ($instance->enrol === 'manual') {
                        $manualinstance = $instance;
                        break;
                    }
                }

                if (!$manualinstance) {
                    $manualplugin = enrol_get_plugin('manual');
                    if ($manualplugin) {
                        $instanceid = $manualplugin->add_instance($course);
                        if ($instanceid) {
                            $manualinstance = $DB->get_record('enrol', ['id' => $instanceid]);
                        }
                    }
                }

                // Enrol trainer. (The enrol plugin automatically assigns the role).
                if ($manualinstance) {
                    $plugin = enrol_get_plugin('manual');
                    if ($plugin) {
                        $plugin->enrol_user(
                            $manualinstance,
                            $trainerid,
                            $trainerrole->id
                        );
                    }
                }
            }

            purge_all_caches();

            redirect(
                new moodle_url('/local/studentmanagement/trainers.php'),
                'Trainer mapped successfully.'
            );
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$PAGE->set_url('/local/studentmanagement/map_trainer_school.php',
    ['trainerid' => $selectedtrainer, 'schoolid' => $selectedschool]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Map Trainer to School');
$PAGE->set_heading('Map Trainer to School');

echo $OUTPUT->header();
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Map Trainer to School</h4>
        </div>
        <div class="card-body">
            <?php if ($error !== '') { ?>
                <div class="alert alert-danger"><?php echo s($error); ?></div>
            <?php } ?>

            <form method="post">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="mb-3">
                    <label class="form-label">Trainer</label>
                    <select name="trainerid" class="form-control" required>
                        <option value="">Select Trainer</option>
                        <?php foreach ($trainers as $trainer) { ?>
                            <option value="<?php echo $trainer->id; ?>" <?php echo ($selectedtrainer == $trainer->id) ? 'selected' : ''; ?>>
                                <?php echo s(fullname($trainer) . ' (' . $trainer->email . ')'); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">School</label>
                    <select name="schoolid" id="schoolid" class="form-control" required>
                        <option value="">Select School</option>
                        <?php foreach ($schools as $school) { ?>
                            <option value="<?php echo $school->id; ?>" <?php echo ($selectedschool == $school->id) ? 'selected' : ''; ?>>
                                <?php echo s($school->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save Mapping</button>
                    <a href="trainers.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('schoolid').addEventListener('change', function() {
    let trainerid = document.querySelector('select[name="trainerid"]').value;
    let url = 'map_trainer_school.php?schoolid=' + encodeURIComponent(this.value);

    if (trainerid) {
        url += '&trainerid=' + encodeURIComponent(trainerid);
    }

    window.location.href = url;
});
</script>

<?php
echo $OUTPUT->footer();
