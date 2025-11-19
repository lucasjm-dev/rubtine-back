<?php

namespace App\Domains\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    const TYPE_SIMPLE = 'simpleUser';
    const TYPE_PROFESSIONAL = 'professionalUser';

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'simpleUser',
        'professionalUser',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime'
    ];


    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function simpleUser()
    {
        return $this->hasOne(SimpleUser::class);
    }

    public function professionalUser()
    {
        return $this->hasOne(ProfessionalUser::class);
    }

    public function getAvailableTypes(): array
    {
        $types = [];

        if ($this->simpleUser()->exists()) {
            $types[] = self::TYPE_SIMPLE;
        }

        if ($this->professionalUser()->exists()) {
            $types[] = self::TYPE_PROFESSIONAL;
        }

        return $types;
    }

    public function autoDetectLoginAs(): ?string
    {
        $types = $this->getAvailableTypes();

        return count($types) === 1 ? $types[0] : null;
    }

    public function getProfilesAttribute()
    {
        $profiles = [];

        if ($this->relationLoaded('simpleUser') && $this->simpleUser) {
            $profiles['simple_user'] = $this->simpleUser;
        }

        if ($this->relationLoaded('professionalUser') && $this->professionalUser) {
            $profiles['professional_user'] = $this->professionalUser;
        }

        return !empty($profiles) ? $profiles : null;
    }


    public function toArray()
    {
        $array = parent::toArray();

        if ($profiles = $this->getProfilesAttribute()) {
            $array['profiles'] = $profiles;
        }

        return $array;
    }
}
