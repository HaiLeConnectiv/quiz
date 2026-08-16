<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('question_number');
            $table->longText('content');
            $table->string('correct_answer');
            $table->unsignedInteger('duration')->default(30);
            $table->timestamps();
            $table->unique(['game_session_id', 'question_number']);
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->foreign('current_question_id')->references('id')->on('questions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropForeign(['current_question_id']);
        });
        Schema::dropIfExists('questions');
    }
};
