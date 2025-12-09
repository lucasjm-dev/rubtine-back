<?php

namespace App\Domains\Tasks\Models\Pivots;

use App\Domains\Tasks\Enums\TaskProfessionalUserStatus;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskProfessional extends Pivot
{
    protected $table = 'task_professional_user';

    protected $casts = [
        'status' => TaskProfessionalUserStatus::class
    ];
}
