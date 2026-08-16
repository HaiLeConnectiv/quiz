<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameQuestionState extends Model
{
    protected $fillable = [
        'game_session_id','question_id','status','started_at','ended_at','answer_revealed'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'answer_revealed' => 'boolean',
    ];
}
