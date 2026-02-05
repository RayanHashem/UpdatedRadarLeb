<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PostgreSQL Connection Test ===\n\n";

// Display connection settings
echo "Connection Settings:\n";
echo "  Host: " . env('DB_HOST') . "\n";
echo "  Port: " . env('DB_PORT') . "\n";
echo "  Database: " . env('DB_DATABASE') . "\n";
echo "  Username: " . env('DB_USERNAME') . "\n";
echo "  Password: " . (env('DB_PASSWORD') ? '***' . substr(env('DB_PASSWORD'), -3) : 'NOT SET') . "\n";
echo "\n";

// Test connection with timeout
echo "Testing connection...\n";

try {
    // Set connection timeout
    config(['database.connections.pgsql.options' => [
        PDO::ATTR_TIMEOUT => 10,
    ]]);
    
    $start = microtime(true);
    $result = DB::connection()->getPdo();
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    
    echo "✅ Connection successful!\n";
    echo "   Connection time: {$elapsed}ms\n";
    
    // Test a simple query
    echo "\nTesting query...\n";
    $queryResult = DB::select('SELECT 1 as test_value, version() as pg_version, current_database() as db_name, current_user as db_user');
    
    echo "✅ Query successful!\n";
    echo "   Test value: " . $queryResult[0]->test_value . "\n";
    echo "   Database: " . $queryResult[0]->db_name . "\n";
    echo "   User: " . $queryResult[0]->db_user . "\n";
    echo "   PostgreSQL Version: " . substr($queryResult[0]->pg_version, 0, 50) . "...\n";
    
    echo "\n🎉 Database connection verified and working!\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed (PDO Exception)!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
    echo "\n";
    echo "Troubleshooting:\n";
    echo "1. Check RDS Security Group allows inbound on port 5432 from your IP\n";
    echo "2. Verify RDS has 'Publicly accessible' set to Yes\n";
    echo "3. Check credentials in .env file\n";
    echo "4. Verify network connectivity: Test-NetConnection -ComputerName " . env('DB_HOST') . " -Port 5432\n";
    exit(1);
    
} catch (Exception $e) {
    echo "❌ Connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Type: " . get_class($e) . "\n";
    echo "\n";
    echo "Troubleshooting:\n";
    echo "1. Check RDS Security Group allows inbound on port 5432 from your IP\n";
    echo "2. Verify RDS has 'Publicly accessible' set to Yes\n";
    echo "3. Check credentials in .env file\n";
    exit(1);
}

