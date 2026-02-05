<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "Testing PostgreSQL connection...\n";
    echo "Host: " . env('DB_HOST') . "\n";
    echo "Database: " . env('DB_DATABASE') . "\n";
    echo "Username: " . env('DB_USERNAME') . "\n";
    echo "\n";
    
    $result = DB::select('SELECT 1 as ok, version() as version');
    
    echo "✅ Connection successful!\n";
    echo "PostgreSQL Version: " . $result[0]->version . "\n";
    echo "Test query result: " . $result[0]->ok . "\n";
    
} catch (Exception $e) {
    echo "❌ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Troubleshooting steps:\n";
    echo "1. Check if pdo_pgsql is enabled: php -m | findstr pdo_pgsql\n";
    echo "2. Verify RDS security group allows inbound on port 5432 from your IP\n";
    echo "3. Check if RDS has public access enabled\n";
    echo "4. Verify credentials in .env file\n";
    exit(1);
}

