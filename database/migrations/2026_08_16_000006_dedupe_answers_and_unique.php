<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $seen = [];
        $rows = DB::table('answers')
            ->select('id', 'game_session_id', 'question_id', 'participant_id')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $key = $row->game_session_id . ':' . $row->question_id . ':' . $row->participant_id;

            if (isset($seen[$key])) {
                DB::table('answers')->where('id', $row->id)->delete();
                continue;
            }

            $seen[$key] = true;
        }

        $indexes = DB::select('SHOW INDEX FROM answers');
        $alreadyHasUnique = collect($indexes)->contains(fn ($index) => $index->Key_name === 'answers_game_session_question_participant_unique');

        if (!$alreadyHasUnique) {
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
