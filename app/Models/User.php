<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'password',
        'privilege',
        'register_date',
        'team_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'updated_at',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function moontrekkerPoints()
    {
        return $this->hasMany(MoonTrekkerPoints::class, 'user_id');
    }

    public function challengeAttempts()
    {
        return $this->hasMany(ChallengeUserAttempt::class, 'user_id')
            ->whereHas('challenge', function ($q) {
                $q->where('type', '!=', 'race')->where('type', '!=', 'training');
            })
            ->where('status', 'complete');
    }

    // public function raceParticipants()
    // {
    //     return $this
    //         ->hasOne(ChallengeUserAttempt::class, 'user_id')
    //         ->orderBy('total_time', 'asc');
    // }

    public function raceAttempt()
    {
        return $this->hasOne(ChallengeUserAttempt::class, 'user_id')
            ->whereHas('challenge', function ($q) {
                $q->where('type', 'race');
            })
            ->where('status', 'complete')
            ->orderBy('total_time', 'asc');
    }

    public function raceBestTimeAttempts()
    {
        return $this->hasOne(ChallengeUserAttempt::class, 'user_id')
            ->whereHas('challenge', function ($q) {
                $q->where('type', 'race');
            })
            ->where('status', 'complete')
            ->orderBy('total_time', 'asc')
            ->orderBy('started_at', 'asc')
            ->orderBy('ended_at', 'asc');
    }
}
