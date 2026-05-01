<?php
require('../../config.php');
require_login();

$id = required_param('id', PARAM_INT);

$DB->delete_records('school', ['id' => $id]);

redirect(new moodle_url('/local/studentmanagement/schools.php'), 'Deleted');