<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeUserAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'challenge_user_attempt';
    protected $primaryKey = 'attempt_id';
    protected $fillable = [
        'challenge_id',
        'user_id',
        'moontrekker_point',
        'total_distance',
        'status',
        'total_time',
        'started_at',
        'ended_at',
    ];

    protected $hidden = [
        'updated_at',
    ];

    public function progress()
    {
        return $this->hasMany(ChallengeUserAttemptTiming::class, 'attempt_id');
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class, 'challenge_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
