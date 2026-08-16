<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->text('answer');
            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamp('final_submitted_at')->nullable();
            $table->unsignedBigInteger('answer_time_ms')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['question_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
