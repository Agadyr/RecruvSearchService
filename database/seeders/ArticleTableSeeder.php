<?php

namespace Database\Seeders;

use App\Models\Article;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ArticleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();
        for ($i = 0; $i < 50; $i++) {
            Article::create([
                'title' => $faker->sentence(3),
                'body' => $faker->paragraph(5),
                'tags' => join(',', $faker->words(4))
            ]);
        }
    }
}
