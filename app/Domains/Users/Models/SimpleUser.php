<?php

namespace App\Domains\Users\Models;

use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpleUser extends Model
{
    use HasFactory, SerializesDatesInAppTimezone;

    protected $fillable = [
        'user_id',
        'birth_date',
        'about_me',
        'profile_photo'
    ];

    protected $hidden = [];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
