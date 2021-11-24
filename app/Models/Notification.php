<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{

    use HasFactory, SoftDeletes;
    protected $table = 'notification';
    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'deep_link',
        'announcement_id',
        'challenge_id',
    ];

    protected $hidden = [];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class, 'challenge_id');
    }

    public function activityFeed()
    {
        return $this->belongsTo(ActivityFeed::class, 'feed_id');
    }

    public function bcoinRecord()
    {
        return $this->belongsTo(BcoinLog::class, 'transaction_id');
    }
}
