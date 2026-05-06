<?php
require('../../config.php');
global $DB;

$toremove = array(1 => 5, 3 => 6);
list($insql, $params) = $DB->get_in_or_equal($toremove);
$params[] = 99; // schoolid
$params[] = 100; // courseid

echo "SQL: gradeid $insql AND schoolid = ? AND courseid = ?\n";
print_r($params);
