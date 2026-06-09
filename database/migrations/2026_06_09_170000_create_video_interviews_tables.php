<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('invited');
            $table->timestamps();
            $table->unique('application_id');
        });

        Schema::create('video_interview_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('video_path')->nullable();
            $table->text('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_interview_answers');
        Schema::dropIfExists('video_interviews');
    }
};
