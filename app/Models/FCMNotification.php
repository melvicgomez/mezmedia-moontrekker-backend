<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FCMNotification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_fcm_token';
    protected $primaryKey = 'token_id';

    protected $hidden = [
        'updated_at',
        'created_at',
    ];

    protected $fillable = [
        'user_id',
        'fcm_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
