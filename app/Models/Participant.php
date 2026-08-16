<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'game_session_id','name','token','status','joined_at','last_seen_at'
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'participant_id');
    }
}
