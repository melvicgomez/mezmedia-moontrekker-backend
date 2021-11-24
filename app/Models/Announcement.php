<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'announcement';
    protected $primaryKey = 'announcement_id';
    protected $fillable = [
        'notification_message',
        'title',
        'description',
        'html_content',
        'cover_image',
        'pin_post',
        'scheduled_at',
        'published_at',
    ];
}
