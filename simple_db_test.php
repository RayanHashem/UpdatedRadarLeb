<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Testing connection...\n";

try {
    $result = DB::select('SELECT 1 as ok, current_database() as db_name, version() as pg_version');
    
    echo "✅ CONNECTION SUCCESSFUL!\n\n";
    echo "Test Result: " . $result[0]->ok . "\n";
    echo "Database: " . $result[0]->db_name . "\n";
    echo "PostgreSQL Version: " . substr($result[0]->pg_version, 0, 60) . "...\n";
    echo "\n🎉 Database is connected and working!\n";
    exit(0);
    
} catch (Exception $e) {
    echo "❌ CONNECTION FAILED!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

