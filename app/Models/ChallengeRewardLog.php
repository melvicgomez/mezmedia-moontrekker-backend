<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeRewardLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'challenge_reward_log';
    protected $primaryKey = 'log_id';
    protected $fillable = [
        'challenge_id',
        'user_id',
        'status',
    ];
}
