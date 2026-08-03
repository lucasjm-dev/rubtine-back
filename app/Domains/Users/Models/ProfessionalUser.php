<?php

namespace App\Domains\Users\Models;

use App\Domains\Categories\Models\Subcategory;
use App\Domains\Users\Enums\Gender;
use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Tasks\Models\Task;
use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalUser extends Model
{
    use HasFactory, SerializesDatesInAppTimezone;

    protected $with = ['subcategory'];

    protected $fillable = [
        'user_id',
        'gender',
        'birth_date',
        'about_me',
        'profile_photo',
        'subcategory_id'
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

    public function genderedTitle(): ?string
    {
        if (!$this->gender || !$this->subcategory) {
            return null;
        }

        $title = $this->subcategory->titleFor($this->gender);

        if (!$title) {
            return null;
        }

        $article = $this->gender === Gender::FEMALE ? 'la' : 'el';

        return "{$article} {$title}";
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
            ->wherePivot('task_role', TaskParticipantTaskRole::PARTICIPANT)
            ->wherePivot('participant_profile', TaskParticipantProfile::PROFESSIONAL)
            ->withPivot(['task_role', 'participant_profile', 'status', 'requested_by_user_id'])
            ->withTimestamps();
    }

    public function assignedTasks()
    {
        return $this->tasks()->wherePivot('status', TaskParticipantStatus::ACCEPTED);
    }
}
