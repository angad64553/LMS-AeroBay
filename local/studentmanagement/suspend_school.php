<?php
require('../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

$school = $DB->get_record('schools', ['id' => $id], '*', MUST_EXIST);

$school->status = $school->status ? 0 : 1;

$DB->update_record('schools', $school);

redirect(new moodle_url('/local/studentmanagement/schools.php'));