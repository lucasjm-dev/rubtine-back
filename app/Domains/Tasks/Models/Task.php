<?php

namespace App\Domains\Tasks\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Categories\Models\Subcategory;
use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory, SerializesDatesInAppTimezone;

    protected $fillable = [
        'title',
        'description',
        'status',
        'public',
        'subcategory_id',
        'beneficiary_id',
    ];

    protected $hidden = [];

    protected $casts = [];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function participants()
    {
        return $this->hasMany(TaskParticipant::class);
    }

    public function events()
    {
        return $this->hasMany(TaskEvent::class);
    }

    public function eventSchedules()
    {
        return $this->hasMany(TaskEventSchedule::class);
    }

    public static function listRelations(): array
    {
        return array_merge(
            [
                'subcategory',
                'beneficiary',
                'participants',
                'eventSchedules'
            ],
            TaskParticipant::profileEagerLoads('participants.user')
        );
    }

    public function scopeWithListRelations(Builder $query): Builder
    {
        return $query->with(self::listRelations());
    }

    public function owners()
    {
        return $this->belongsToMany(
            User::class,
            'task_participants',
            'task_id',
            'user_id'
        )
            ->wherePivot('task_role', TaskParticipantTaskRole::OWNER)
            ->withPivot(['task_role', 'participant_profile', 'status', 'requested_by_user_id'])
            ->withTimestamps();
    }

    public function professionals()
    {
        return $this->belongsToMany(
            ProfessionalUser::class,
            'task_participants',
            'task_id',
            'user_id',
            'id',
            'user_id'
        )
            ->wherePivot('task_role', TaskParticipantTaskRole::PARTICIPANT)
            ->wherePivot('participant_profile', TaskParticipantProfile::PROFESSIONAL)
            ->withPivot(['task_role', 'participant_profile', 'status', 'requested_by_user_id'])
            ->withTimestamps();
    }

    public function assignedProfessionals()
    {
        return $this->professionals()
            ->wherePivot('status', TaskParticipantStatus::ACCEPTED);
    }

    /**
     * Participantes con perfil profesional aceptados, sin filtrar por rol:
     * cubre tanto al profesional que creó la tarea (OWNER) como al que se
     * sumó a una tarea ajena (PARTICIPANT). Se prioriza al OWNER.
     */
    public function acceptedProfessionalParticipants()
    {
        return $this->participants()
            ->professionals()
            ->where('status', TaskParticipantStatus::ACCEPTED)
            ->orderByRaw('CASE WHEN task_role = ? THEN 0 ELSE 1 END', [TaskParticipantTaskRole::OWNER]);
    }


    public function scopeAssignedToProfessional(
        Builder $query,
        ProfessionalUser $professional
    ): Builder {
        return $query->whereHas('assignedProfessionals', function ($q) use ($professional) {
            $q->where('professional_users.id', $professional->id);
        });
    }

    public function scopeAvailableForProfessional(
        Builder $query,
        ProfessionalUser $professional
    ): Builder {
        return $query
            ->where('subcategory_id', $professional->subcategory_id)
            ->whereDoesntHave('professionals', function ($q) use ($professional) {
                $q->where('professional_users.id', $professional->id)
                    ->whereNotIn('task_participants.status', [
                        TaskParticipantStatus::CANCELED,
                    ]);
            });
    }

    public function scopeOwnedByUser(
        Builder $query,
        User $user
    ): Builder {
        return $query->whereHas('participants', function ($q) use ($user) {
            $q->owners()->where('user_id', $user->id);
        });
    }


    public function isOwnedBy(User $user): bool
    {
        return $this->participants()
            ->owners()
            ->where('user_id', $user->id)
            ->exists();
    }
}
