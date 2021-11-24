<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Corporate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'corporates';
    protected $primaryKey = 'corporate_id';

    protected $fillable = [
        'business_name',
        'logo_filename',
    ];

    public function team()
    {
        return $this->hasMany(Team::class, 'corporate_id');
    }
}
