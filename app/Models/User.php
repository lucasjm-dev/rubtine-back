<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    const TYPE_SIMPLE = 'simple';
    const TYPE_PROFESSIONAL = 'professional';

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'remember_token',
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

    public static function availableTypes(): array
    {
        return [
            self::TYPE_SIMPLE,
            self::TYPE_PROFESSIONAL,
        ];
    }

    public function canLoginAs(string $type): bool
    {
        switch ($type) {
            case self::TYPE_SIMPLE:
                return (bool) $this->simpleUser;
            case self::TYPE_PROFESSIONAL:
                return (bool) $this->professionalUser;
            default:
                return false;
        }
    }
}
