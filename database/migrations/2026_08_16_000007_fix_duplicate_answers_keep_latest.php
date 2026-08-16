<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGroups = DB::table('answers')
            ->select(
                'game_session_id',
                'question_id',
                'participant_id',
                DB::raw('MAX(id) as keep_id')
            )
            ->groupBy('game_session_id', 'question_id', 'participant_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('answers')
                ->where('game_session_id', $group->game_session_id)
                ->where('question_id', $group->question_id)
                ->where('participant_id', $group->participant_id)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        $indexes = DB::select('SHOW INDEX FROM answers');
        $hasUnique = collect($indexes)->contains(fn ($index) => $index->Key_name === 'answers_game_session_question_participant_unique');

        if (!$hasUnique) {
            Schema::table('answers', function (Blueprint $table) {
                $table->unique(['game_session_id', 'question_id', 'participant_id'], 'answers_game_session_question_participant_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropUnique('answers_game_session_question_participant_unique');
        });
    }
};
