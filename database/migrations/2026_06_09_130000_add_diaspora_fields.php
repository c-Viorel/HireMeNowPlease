<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_job_preferences', function (Blueprint $table) {
            $table->string('current_country')->nullable()->after('availability');
            $table->boolean('open_to_relocation')->default(false)->after('current_country');
            $table->string('timezone')->nullable()->after('open_to_relocation');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('offers_relocation')->default(false)->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_job_preferences', function (Blueprint $table) {
            $table->dropColumn(['current_country', 'open_to_relocation', 'timezone']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('offers_relocation');
        });
    }
};
