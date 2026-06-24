<?php

namespace App\Domains\Users\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Support\Users\UserProfiles;
use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SerializesDatesInAppTimezone;

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

    public function getHidden()
    {
        return array_values(array_unique(array_merge(
            parent::getHidden(),
            UserProfiles::relationNames()
        )));
    }

    public function getAvailableTypes(): array
    {
        $types = [];

        foreach (UserProfiles::all() as $type => $definition) {
            if ($this->{$definition['relation']}()->exists()) {
                $types[] = $type;
            }
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

        foreach (UserProfiles::all() as $type => $definition) {
            $relation = $definition['relation'];

            if ($this->relationLoaded($relation) && $this->{$relation}) {
                $profiles[$definition['response_key']] = $this->{$relation};
            }
        }

        return !empty($profiles) ? $profiles : null;
    }

    public function loggedAs(): ?string
    {
        return auth()->payload()->get('login_as');
    }

    public function isLoggedAsProfile(string $profileType): bool
    {
        return $this->loggedAs() === $profileType;
    }

    public function isLoggedAsProfessional(): bool
    {
        return $this->isLoggedAsProfile(self::TYPE_PROFESSIONAL);
    }

    public function isLoggedAsSimple(): bool
    {
        return $this->isLoggedAsProfile(self::TYPE_SIMPLE);
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
            ->wherePivot('task_role', TaskParticipantTaskRole::OWNER)
            ->withPivot(['task_role', 'participant_profile', 'status', 'requested_by_user_id'])
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
