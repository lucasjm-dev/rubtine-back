<?php

namespace App\Domains\Users\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    const TYPE_SIMPLE = 'simpleUser';
    const TYPE_PROFESSIONAL = 'professionalUser';
    const TYPE_COMPANY = 'companyUser';

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
        'companyUser'
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

    public function companyUser()
    {
        return $this->hasOne(CompanyUser::class);
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

        if ($this->companyUser()->exists()) {
            $types[] = self::TYPE_COMPANY;
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

        if ($this->relationLoaded('companyUser') && $this->companyUser) {
            $profiles['company_user'] = $this->companyUser;
        }

        return !empty($profiles) ? $profiles : null;
    }

    public function loggedAs(): ?string
    {
        return auth()->payload()->get('login_as');
    }

    public function isLoggedAsProfessional(): bool
    {
        return $this->loggedAs() === self::TYPE_PROFESSIONAL;
    }

    public function isLoggedAsSimple(): bool
    {
        return $this->loggedAs() === self::TYPE_SIMPLE;
    }


    public function toArray()
    {
        $array = parent::toArray();

        if ($profiles = $this->getProfilesAttribute()) {
            $array['profiles'] = $profiles;
        }

        return $array;
    }

    public function ownedTasks()
    {
        return $this->belongsToMany(
            Task::class,
            'task_participants',
            'user_id',
            'task_id'
        )
            ->wherePivot('role', TaskParticipantRole::OWNER)
            ->withPivot(['role', 'status', 'requested_by_user_id'])
            ->withTimestamps();
    }

    public function createdBeneficiaries()
    {
        return $this->hasMany(Beneficiary::class, 'created_by_user_id');
    }

    public function linkedBeneficiaries()
    {
        return $this->hasMany(Beneficiary::class, 'user_id');
    }
}
