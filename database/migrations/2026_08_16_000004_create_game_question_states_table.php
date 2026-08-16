<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_question_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('answer_revealed')->default(false);
            $table->timestamps();
            $table->index(['game_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_question_states');
    }
};
