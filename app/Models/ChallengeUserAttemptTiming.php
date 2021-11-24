<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeUserAttemptTiming extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'challenge_user_attempt_timings';
    protected $primaryKey = 'progress_id';
    protected $fillable = [
        'attempt_id',
        'challenge_id',
        'user_id',
        'start_trail_id',
        'end_trail_id',
        'description',
        'scanned_qr_code',
        'submitted_image',
        'moontrekker_point_received',
        'distance',
        'started_at',
        'ended_at',
        'duration',
        'location_data',
        'submitted_at',
        'longitude',
        'latitude',
    ];

    public function attempt()
    {
        return $this->belongsTo(ChallengeUserAttempt::class, 'attempt_id');
    }
}
