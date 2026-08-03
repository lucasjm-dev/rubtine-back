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
            'Salud' => [
                ['name' => 'Psicología', 'title_male' => 'psicólogo', 'title_female' => 'psicóloga'],
                ['name' => 'Nutrición', 'title_male' => 'nutricionista', 'title_female' => 'nutricionista'],
            ],
        ];

        foreach ($categories as $categoryName => $subcategories) {
            $category = ModelsCategory::updateOrCreate(['name' => $categoryName]);

            foreach ($subcategories as $subcategory) {
                $data = is_array($subcategory) ? $subcategory : ['name' => $subcategory];

                ModelsSubcategory::updateOrCreate(
                    ['name' => $data['name'], 'category_id' => $category->id],
                    $data
                );
            }
        }
    }
}
