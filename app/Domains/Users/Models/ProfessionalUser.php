<?php

namespace App\Domains\Users\Models;

use App\Domains\Categories\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'birthday',
        'about_me',
        'profile_photo',
        'subcategory_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function companies()
    {
        return $this->belongsToMany(CompanyUser::class, 'company_user_professional_user')
            ->withTimestamps()
            ->withPivot('approved');
    }
}
