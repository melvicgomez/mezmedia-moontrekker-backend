<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeTrailImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'challenge_trail_image';
    protected $primaryKey = 'image_id';

    protected $fillable = [
        'trail_id',
        'description',
        'image_filename',
        'image_base64',
    ];
}
