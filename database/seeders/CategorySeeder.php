<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // setup default
        $dataCategories = [
            [
                'name' => 'រមណីយដ្ឋានធម្មជាតិ',
                'create_uid' => 1,
                'update_uid' => 1
            ],
            [
                'name' => 'រមណីយដ្ឋានប្រវត្តិសាស្រ្ត',
                'create_uid' => 1,
                'update_uid' => 1
            ],
            [
                'name' => 'រមណីយដ្ឋានវប្បធម៌',
                'create_uid' => 1,
                'update_uid' => 1
            ],
        ];

        // store category
        foreach ($dataCategories as $dataCategory) {
            $category = new Category($dataCategory);
            $category->image = Category::DEFAULT_IMAGE;
            $category->save();
        }
    }
}
