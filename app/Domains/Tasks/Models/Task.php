<?php

namespace App\Domains\Tasks\Models;

use App\Domains\Categories\Models\Subcategory;
use App\Domains\Tasks\Enums\TaskRequestStatus;
use App\Domains\Tasks\Models\Pivots\TaskRequest;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'public',
        'subcategory_id',
    ];

    protected $hidden = [];

    protected $casts = [];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function professionals()
    {
        return $this->belongsToMany(ProfessionalUser::class, 'task_professional_user')
            ->using(TaskRequest::class)
            ->withPivot(['status'])
            ->withTimestamps();
    }

    public function assignedProfessionals()
    {
        return $this->professionals()
            ->wherePivot('status', TaskRequestStatus::ACCEPTED);
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
                    ->whereNotIn('task_professional_user.status', [
                        TaskRequestStatus::CANCELED,
                    ]);
            });
    }


    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
