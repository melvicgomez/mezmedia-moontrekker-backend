<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'teams';
    protected $primaryKey = 'team_id';

    protected $fillable = [
        'team_name',
        'team_type',
        'corporate_id'
    ];

    public function participants()
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function corporate()
    {
        return $this->belongsTo(Corporate::class, 'corporate_id');
    }
}
