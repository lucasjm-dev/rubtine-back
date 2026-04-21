<?php

namespace App\Domains\Patients\Models;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'phone',
        'email',
        'user_id',
        'created_by_user_id',
    ];

    protected $hidden = [];

    protected $casts = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function scopeAccessibleToUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by_user_id', $user->id);
        });
    }
}
