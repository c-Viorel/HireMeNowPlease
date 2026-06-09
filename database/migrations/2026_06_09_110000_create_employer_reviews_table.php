<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->boolean('would_apply_again')->default(true);
            $table->text('body')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->string('status')->default('published');
            $table->timestamps();
            $table->unique('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_reviews');
    }
};
