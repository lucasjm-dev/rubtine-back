<?php

namespace App\Domains\Categories\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $with = ['category'];

    protected $fillable = [
        'name',
        'category_id',
    ];

    protected $hidden = ['category_id'];

    protected $casts = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
