<?php

namespace App\Domains\Categories\Models;

use App\Domains\Users\Enums\Gender;
use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory, SerializesDatesInAppTimezone;

    protected $with = ['category'];

    protected $fillable = [
        'name',
        'title_male',
        'title_female',
        'category_id',
    ];

    protected $hidden = ['category_id'];

    protected $casts = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function titleFor(?string $gender): ?string
    {
        if ($gender === Gender::MALE) {
            return $this->title_male;
        }

        if ($gender === Gender::FEMALE) {
            return $this->title_female;
        }

        return null;
    }
}
