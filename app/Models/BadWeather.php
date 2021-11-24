<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BadWeather extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'weather_warning';
    protected $primaryKey = 'warning_id';
    protected $fillable = [
        'title',
        'message',
        'created_at',
        'updated_at',
        'deleted_at',
        'started_at',
        'ended_at',
    ];
}
