<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('skill_tag');
            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->timestamps();
        });

        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('prompt');
            $table->json('choices');
            $table->unsignedTinyInteger('correct_index');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->boolean('passed')->default(false);
            $table->boolean('badge_awarded')->default(false);
            $table->timestamps();
            $table->unique(['assessment_id', 'candidate_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
    }
};
