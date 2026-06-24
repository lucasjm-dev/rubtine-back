<?php

namespace App\Domains\Categories\Models;

use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, SerializesDatesInAppTimezone;

    protected $fillable = [
        'name',
    ];

    protected $hidden = [];

    protected $casts = [];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
}
