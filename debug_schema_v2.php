<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function getCols($table) {
    try {
        return implode(", ", Schema::getColumnListing($table));
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

$output = "acd_student_address: " . getCols('acd_student_address') . "\n";
$output .= "mstr_district: " . getCols('mstr_district') . "\n";

file_put_contents('schema_check.txt', $output);
echo "Done\n";
