<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challenge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'challenges';
    protected $primaryKey = 'challenge_id';
    protected $fillable = [
        'title',
        'description',
        'html_content',
        'difficulty',
        'multiple_attempt_available',
        'moontrekker_point',
        'notification_message',
        'distance',
        'reward_count',
        'is_time_required',
        'is_distance_required',
        'type',
        'trail_overview_html',
        'checkpoint_preview_image',
        'challenge_cover_image',
        'started_at',
        'ended_at',
        'schedule_at',
        'published_at'
    ];

    public function trails()
    {
        return $this->hasMany(ChallengeTrail::class, 'challenge_id')->whereNull('deleted_at')->with('images')->orderBy('trail_index', 'asc');
    }

    public function countRewardRedeemed()
    {
        return $this->hasMany(ChallengeRewardLog::class, 'challenge_id');
    }

    public function participants()
    {
        return $this->hasMany(ChallengeUserAttempt::class, 'challenge_id')->where('status', 'complete');
    }
}
