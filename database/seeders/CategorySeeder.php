<?php

namespace Database\Seeders;

use App\Domains\Categories\Models\Category as ModelsCategory;
use App\Domains\Categories\Models\Subcategory as ModelsSubcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Tecnología' => ['Desarrollo web', 'IA / Machine Learning', 'Ciberseguridad'],
            'Diseño' => ['UI/UX', 'Diseño gráfico', 'Animación 3D'],
            'Marketing' => ['SEO', 'Publicidad digital', 'Community management'],
            'Educación' => ['Docencia', 'Formación online', 'Idiomas'],
        ];

        foreach ($categories as $categoryName => $subcategories) {
            $category = ModelsCategory::create(['name' => $categoryName]);

            foreach ($subcategories as $subName) {
                ModelsSubcategory::create([
                    'name' => $subName,
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}
