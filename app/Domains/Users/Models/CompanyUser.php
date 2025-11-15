<?php

namespace App\Domains\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'birthday',
        'about_me',
        'tax_id',
        'business_name',
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

    public function professionals()
    {
        return $this->belongsToMany(ProfessionalUser::class, 'company_user_professional_user')
            ->withTimestamps()
            ->withPivot('approved');
    }
}
