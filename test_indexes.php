<?php
require('config.php');
global $DB;
print_r($DB->get_indexes('school_course_map'));
