<?php
require('../../config.php');
require_login();

global $DB, $OUTPUT, $PAGE;

$context = context_system::instance();

if (!is_siteadmin()) {
    redirect('/', 'Access denied');
}

$editschoolid = optional_param('schoolid', 0, PARAM_INT);
$editcourseid = optional_param('courseid', 0, PARAM_INT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);
$deleteschoolid = optional_param('deleteschoolid', 0, PARAM_INT);
$deletecourseid = optional_param('deletecourseid', 0, PARAM_INT);
$error = '';

if ($deleteschoolid && $deletecourseid) {
    require_sesskey();
    $DB->delete_records('school_course_map', ['schoolid' => $deleteschoolid, 'courseid' => $deletecourseid]);
    redirect(new moodle_url('/local/studentmanagement/grades.php'), 'Grade course mapping deleted successfully.');
}

if ($deleteid) {
    require_sesskey();
    $DB->delete_records('school_course_map', ['id' => $deleteid]);
    redirect(new moodle_url('/local/studentmanagement/grades.php'), 'Grade course mapping deleted successfully.');
}

$schools = $DB->get_records('school', null, 'name ASC');
$grades = $DB->get_records('grade', null, 'name ASC');
$courses = $DB->get_records_select('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname ASC', 'id, fullname');

$selectedgradeids = [];
$selectedcourseids = [];
$editmode = !empty($editschoolid) && !empty($editcourseid);

if ($editmode) {
    $existinggrades = $DB->get_records('school_course_map',
        ['schoolid' => $editschoolid, 'courseid' => $editcourseid]);

    foreach ($existinggrades as $existinggrade) {
        $selectedgradeids[] = $existinggrade->gradeid;
    }

    $selectedcourseids[] = $editcourseid;
} else if (!empty($editcourseid)) {
    $selectedcourseids[] = $editcourseid;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $schoolid = required_param('schoolid', PARAM_INT);
    $courseids = optional_param_array('courseids', [], PARAM_INT);
    $gradeids = optional_param_array('gradeids', [], PARAM_INT);
    $originalschoolid = optional_param('originalschoolid', 0, PARAM_INT);
    $originalcourseid = optional_param('originalcourseid', 0, PARAM_INT);
    $courseids = array_values(array_unique($courseids));
    $gradeids = array_values(array_unique($gradeids));

    if (empty($schools[$schoolid])) {
        $error = 'Select a valid school.';
    } else if (empty($courseids)) {
        $error = 'Select at least one course.';
    } else if (empty($gradeids)) {
        $error = 'Select at least one grade.';
    } else {
        foreach ($courseids as $courseid) {
            if (empty($courses[$courseid])) {
                $error = 'Select valid courses only.';
                break;
            }
        }

        foreach ($gradeids as $gradeid) {
            if (empty($grades[$gradeid])) {
                $error = 'Select valid grades only.';
                break;
            }
        }

        if ($error === '') {
            $transaction = $DB->start_delegated_transaction();

            try {
                if ($originalschoolid && $originalcourseid) {
                    if ($originalschoolid != $schoolid || !in_array($originalcourseid, $courseids)) {
                        $DB->delete_records('school_course_map',
                            ['schoolid' => $originalschoolid, 'courseid' => $originalcourseid]);
                    }
                }

                foreach ($courseids as $courseid) {
                    // Fetch all existing mappings for this school and course.
                    $existingrecords = $DB->get_records('school_course_map', ['schoolid' => $schoolid, 'courseid' => $courseid]);
                    
                    $existinggrades = []; // gradeid => id
                    $recordstodelete = [];
                    
                    foreach ($existingrecords as $rec) {
                        // Catch any duplicates already in the DB and mark them for deletion.
                        if (array_key_exists($rec->gradeid, $existinggrades)) {
                            $recordstodelete[] = $rec->id;
                        } else {
                            $existinggrades[$rec->gradeid] = $rec->id;
                        }
                    }
                    
                    // Identify records to remove (grades no longer selected).
                    foreach ($existinggrades as $gradeid => $recid) {
                        if (!in_array($gradeid, $gradeids)) {
                            $recordstodelete[] = $recid;
                            unset($existinggrades[$gradeid]); // Remove from our tracking so it's accurate
                        }
                    }
                    
                    // Delete all unused or duplicate mappings by their exact ID.
                    if (!empty($recordstodelete)) {
                        list($insql, $params) = $DB->get_in_or_equal($recordstodelete);
                        $DB->delete_records_select('school_course_map', "id $insql", $params);
                    }
                    
                    // Identify new grades to add.
                    $currentgradeids = array_keys($existinggrades);
                    $toadd = array_diff($gradeids, $currentgradeids);
                    
                    foreach ($toadd as $gradeid) {
                        $record = new stdClass();
                        $record->schoolid = $schoolid;
                        $record->gradeid = $gradeid;
                        $record->courseid = $courseid;
                        $DB->insert_record('school_course_map', $record);
                    }
                }

                $transaction->allow_commit();
                $message = ($originalschoolid && $originalcourseid)
                    ? 'Grade course mappings updated successfully.'
                    : 'Grade course mappings added successfully.';
                redirect(new moodle_url('/local/studentmanagement/grades.php'), $message);
            } catch (Exception $e) {
                $transaction->rollback($e);
                $error = 'Error updating mappings: ' . $e->getMessage();
            }
        }
    }

    $editschoolid = $schoolid;
    $editcourseid = reset($courseids) ?: 0;
    $selectedcourseids = $courseids;
    $selectedgradeids = $gradeids;
}

$mappingrecords = $DB->get_records_sql("
    SELECT scm.id, scm.schoolid, scm.gradeid, scm.courseid,
           s.name AS schoolname,
           g.name AS gradename,
           c.fullname AS coursename
      FROM {school_course_map} scm
      JOIN {school} s ON s.id = scm.schoolid
      JOIN {grade} g ON g.id = scm.gradeid
      JOIN {course} c ON c.id = scm.courseid
  ORDER BY s.name, c.fullname, g.name
");

$mappings = [];
$courseallocations = [];

foreach ($courses as $course) {
    $courseallocations[$course->id] = (object) [
        'courseid' => $course->id,
        'coursename' => $course->fullname,
        'allocations' => [],
    ];
}

foreach ($mappingrecords as $record) {
    $key = $record->schoolid . '-' . $record->courseid;

    if (!isset($mappings[$key])) {
        $mappings[$key] = (object) [
            'schoolid' => $record->schoolid,
            'courseid' => $record->courseid,
            'schoolname' => $record->schoolname,
            'coursename' => $record->coursename,
            'grades' => [],
        ];
    }

    $mappings[$key]->grades[] = $record->gradename;

    if (!isset($courseallocations[$record->courseid])) {
        $courseallocations[$record->courseid] = (object) [
            'courseid' => $record->courseid,
            'coursename' => $record->coursename,
            'allocations' => [],
        ];
    }

    if (!isset($courseallocations[$record->courseid]->allocations[$record->schoolid])) {
        $courseallocations[$record->courseid]->allocations[$record->schoolid] = (object) [
            'schoolname' => $record->schoolname,
            'grades' => [],
        ];
    }

    $courseallocations[$record->courseid]->allocations[$record->schoolid]->grades[] = $record->gradename;
}

$PAGE->set_url('/local/studentmanagement/grades.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Grades');
$PAGE->set_heading('Grades');

echo $OUTPUT->header();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Grades</h3>
        <a href="schools.php" class="btn btn-secondary">Back to Schools</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><?php echo $editmode ? 'Edit Grade Course Mapping' : 'Add Grade Course Mapping'; ?></h4>
        </div>
        <div class="card-body">
            <?php if ($error !== '') { ?>
                <div class="alert alert-danger"><?php echo s($error); ?></div>
            <?php } ?>

            <form method="post">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <input type="hidden" name="originalschoolid" value="<?php echo $editmode ? $editschoolid : 0; ?>">
                <input type="hidden" name="originalcourseid" value="<?php echo $editmode ? $editcourseid : 0; ?>">

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">School</label>
                        <select name="schoolid" class="form-control" required>
                            <option value="">Select School</option>
                            <?php foreach ($schools as $school) { ?>
                                <option value="<?php echo $school->id; ?>" <?php echo ($editschoolid == $school->id) ? 'selected' : ''; ?>>
                                    <?php echo s($school->name); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Courses</label>
                        <div class="border rounded p-2" style="max-height:180px; overflow:auto;">
                            <label class="d-block mb-2">
                                <input type="checkbox" id="selectallcourses">
                                <strong>Select All</strong>
                            </label>
                            <?php foreach ($courses as $course) { ?>
                                <label class="d-block">
                                    <input type="checkbox" name="courseids[]" class="course-checkbox"
                                        value="<?php echo $course->id; ?>"
                                        <?php echo in_array($course->id, $selectedcourseids) ? 'checked' : ''; ?>>
                                    <?php echo s($course->fullname); ?>
                                </label>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Grades</label>
                        <div class="border rounded p-2" style="max-height:180px; overflow:auto;">
                            <label class="d-block mb-2">
                                <input type="checkbox" id="selectallgrades">
                                <strong>Select All</strong>
                            </label>
                            <?php foreach ($grades as $grade) { ?>
                                <label class="d-block">
                                    <input type="checkbox" name="gradeids[]" class="grade-checkbox"
                                        value="<?php echo $grade->id; ?>"
                                        <?php echo in_array($grade->id, $selectedgradeids) ? 'checked' : ''; ?>>
                                    <?php echo s($grade->name); ?>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editmode ? 'Update Mapping' : 'Add Mapping'; ?>
                    </button>
                    <?php if ($editmode) { ?>
                        <a href="grades.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>

    <h4>All Courses Allocation Status</h4>
    <table class="table table-bordered table-hover mb-4">
        <thead class="table-dark">
            <tr>
                <th>Course</th>
                <th>Mapped School / Grades</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($courseallocations)) { ?>
                <?php foreach ($courseallocations as $courseallocation) { ?>
                    <tr>
                        <td><?php echo s($courseallocation->coursename); ?></td>
                        <td>
                            <?php if (!empty($courseallocation->allocations)) { ?>
                                <?php foreach ($courseallocation->allocations as $allocation) { ?>
                                    <div>
                                        <strong><?php echo s($allocation->schoolname); ?>:</strong>
                                        <?php echo s(implode(', ', $allocation->grades)); ?>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <span class="badge badge-warning">Not allocated to any school/grade</span>
                            <?php } ?>
                        </td>
                        <td class="text-center">
                            <a href="grades.php?courseid=<?php echo $courseallocation->courseid; ?>" class="btn btn-primary btn-sm">
                                Map Course
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="3" class="text-center">No Moodle courses found.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <h4>School Grade Course Mappings</h4>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>School</th>
                <th>Course</th>
                <th>Grades</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($mappings)) { ?>
                <?php foreach ($mappings as $row) { ?>
                    <tr>
                        <td><?php echo s($row->schoolname); ?></td>
                        <td><?php echo s($row->coursename); ?></td>
                        <td><?php echo s(implode(', ', $row->grades)); ?></td>
                        <td class="text-center">
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="grades.php?schoolid=<?php echo $row->schoolid; ?>&courseid=<?php echo $row->courseid; ?>" class="btn btn-info btn-sm">Edit</a>
                                <a href="grades.php?deleteschoolid=<?php echo $row->schoolid; ?>&deletecourseid=<?php echo $row->courseid; ?>&sesskey=<?php echo sesskey(); ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this mapping?')">
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="4" class="text-center">No grade course mappings found.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function syncSelectAllCourses() {
    let courses = document.querySelectorAll('.course-checkbox');
    let checkedCourses = document.querySelectorAll('.course-checkbox:checked');
    document.getElementById('selectallcourses').checked = courses.length > 0 && courses.length === checkedCourses.length;
}

function syncSelectAllGrades() {
    let grades = document.querySelectorAll('.grade-checkbox');
    let checkedGrades = document.querySelectorAll('.grade-checkbox:checked');
    document.getElementById('selectallgrades').checked = grades.length > 0 && grades.length === checkedGrades.length;
}

document.getElementById('selectallcourses').addEventListener('change', function() {
    document.querySelectorAll('.course-checkbox').forEach(function(checkbox) {
        checkbox.checked = document.getElementById('selectallcourses').checked;
    });
});

document.getElementById('selectallgrades').addEventListener('change', function() {
    document.querySelectorAll('.grade-checkbox').forEach(function(checkbox) {
        checkbox.checked = document.getElementById('selectallgrades').checked;
    });
});

document.querySelectorAll('.course-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', syncSelectAllCourses);
});

document.querySelectorAll('.grade-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', syncSelectAllGrades);
});

syncSelectAllCourses();
syncSelectAllGrades();
</script>

<?php
echo $OUTPUT->footer();
