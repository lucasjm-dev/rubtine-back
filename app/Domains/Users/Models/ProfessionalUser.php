<?php

namespace App\Domains\Users\Models;

use App\Domains\Categories\Models\Subcategory;
use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalUser extends Model
{
    use HasFactory;

    protected $with = ['subcategory'];

    protected $fillable = [
        'user_id',
        'birth_date',
        'about_me',
        'profile_photo'
    ];

    protected $hidden = ['subcategory_id'];

    protected $casts = [
        'birth_date' => 'date',
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

    public function tasks()
    {
        return $this->belongsToMany(
            Task::class,
            'task_participants',
            'user_id',
            'task_id',
            'user_id',
            'id'
        )
            ->wherePivot('role', TaskParticipantRole::PROFESSIONAL)
            ->withPivot(['role', 'status', 'requested_by_user_id'])
            ->withTimestamps();
    }

    public function assignedTasks()
    {
        return $this->tasks()->wherePivot('status', TaskParticipantStatus::ACCEPTED);
    }
}
