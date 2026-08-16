<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'game_session_id','question_number','content','correct_answer','duration'
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_id');
    }
}
