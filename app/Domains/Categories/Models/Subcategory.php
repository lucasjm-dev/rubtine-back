<?php

namespace App\Domains\Categories\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
    ];

    protected $hidden = [];

    protected $casts = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
