<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "acd_student_address:\n";
echo implode(", ", Schema::getColumnListing('acd_student_address')) . "\n\n";

echo "mstr_district:\n";
echo implode(", ", Schema::getColumnListing('mstr_district')) . "\n";
