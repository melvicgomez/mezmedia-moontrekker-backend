<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeTrail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'challenge_trail';
    protected $primaryKey = 'trail_id';
    protected $fillable = [
        'challenge_id',
        'title',
        'description',
        'html_content',
        'trail_index',
        'trail_progress_image',
        'distance',
        'station_qr_value',
        'moontrekker_points',
    ];

    public function images()
    {
        return $this->hasMany(ChallengeTrailImage::class, 'trail_id');
    }
}
