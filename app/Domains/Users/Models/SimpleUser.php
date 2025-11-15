<?php

namespace App\Domains\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpleUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'birthday',
        'about_me',
        'profile_photo'
    ];

    protected $hidden = [];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
