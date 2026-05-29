<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->json('fit_snapshot')->nullable()->after('profile_snapshot');
            $table->json('responsiveness_snapshot')->nullable()->after('fit_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['fit_snapshot', 'responsiveness_snapshot']);
        });
    }
};
