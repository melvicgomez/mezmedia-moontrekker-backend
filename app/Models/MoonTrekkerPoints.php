<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoonTrekkerPoints extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'moontrekker_point';
    protected $primaryKey = 'record_id';

    protected $fillable = [
        'user_id',
        'amount',
        'challenge_id',
        'attempt_id',
        'description',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // belongsTo
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
