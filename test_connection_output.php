<?php

$output = [];
$output[] = "=== Database Connection Test ===";
$output[] = "Started at: " . date('Y-m-d H:i:s');
$output[] = "";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $output[] = "Laravel bootstrapped successfully";
    
    $output[] = "Testing database connection...";
    $output[] = "Host: " . env('DB_HOST');
    $output[] = "Database: " . env('DB_DATABASE');
    $output[] = "";
    
    $start = microtime(true);
    $result = Illuminate\Support\Facades\DB::select('SELECT 1 as test, current_database() as db, version() as version');
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    
    $output[] = "✅ CONNECTION SUCCESSFUL!";
    $output[] = "Connection time: {$elapsed}ms";
    $output[] = "";
    $output[] = "Test Result: " . $result[0]->test;
    $output[] = "Database: " . $result[0]->db;
    $output[] = "PostgreSQL Version: " . substr($result[0]->version, 0, 50);
    $output[] = "";
    $output[] = "🎉 Database connection verified and working!";
    
} catch (PDOException $e) {
    $output[] = "❌ CONNECTION FAILED (PDO Exception)!";
    $output[] = "Error: " . $e->getMessage();
    $output[] = "Code: " . $e->getCode();
    
} catch (Exception $e) {
    $output[] = "❌ CONNECTION FAILED!";
    $output[] = "Error: " . $e->getMessage();
    $output[] = "Type: " . get_class($e);
}

$output[] = "";
$output[] = "Completed at: " . date('Y-m-d H:i:s');

$result = implode("\n", $output);
echo $result;
file_put_contents(__DIR__ . '/connection_test_result.txt', $result);

