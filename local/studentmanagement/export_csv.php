<?php
require('../../config.php');
require_login();

$schools = $DB->get_records('schools');

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=schools.csv');

$output = fopen("php://output", "w");

fputcsv($output, ['Name', 'Email', 'Phone', 'Region']);

foreach ($schools as $s) {
    fputcsv($output, [$s->schoolname, $s->email, $s->phone, $s->region]);
}

fclose($output);
exit;