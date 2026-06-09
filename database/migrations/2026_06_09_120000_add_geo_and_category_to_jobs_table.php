<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('category')->default('white_collar')->after('experience_level');
            $table->string('shift_schedule')->nullable()->after('category');
            $table->decimal('latitude', 10, 7)->nullable()->after('shift_schedule');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['category', 'shift_schedule', 'latitude', 'longitude']);
        });
    }
};
