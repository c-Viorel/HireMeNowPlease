<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_scorecard_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_scorecard_id')->constrained()->cascadeOnDelete();
            $table->string('criterion');
            $table->unsignedTinyInteger('score')->default(0);
            $table->text('evidence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_scorecard_items');
    }
};
