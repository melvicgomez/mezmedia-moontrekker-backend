<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OneTimePin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_one_time_pin';
    protected $primaryKey = 'otp_id';

    protected $fillable = [
        'user_id',
        'otp_code',
        'is_used',
    ];
}
