<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('join_code')->unique();
            $table->string('password')->nullable();
            $table->boolean('password_enabled')->default(false);
            $table->string('status')->default('draft');
            $table->foreignId('current_question_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
