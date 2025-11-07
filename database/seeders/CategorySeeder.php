<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

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
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $subName) {
                Subcategory::create([
                    'name' => $subName,
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}
