<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('cost');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('location_accuracy', 8, 2)->nullable()->after('longitude');
            $table->timestamp('location_at')->nullable()->after('location_accuracy');
            $table->string('ip_address', 45)->nullable()->after('location_at');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->index(['user_id', 'ip_address', 'created_at'], 'scans_user_ip_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropIndex('scans_user_ip_created_index');
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_accuracy',
                'location_at',
                'ip_address',
                'user_agent',
            ]);
        });
    }
};
