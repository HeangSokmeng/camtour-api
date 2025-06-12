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
                'name_km' => 'រមណីយដ្ឋាន ធម្មជាតិ',
                'name' => 'Natural resort',
                'create_uid' => 1,
                'update_uid' => 1
            ],
            [
                'name_km' => 'រមណីយដ្ឋាន ប្រវត្តិសាស្រ្ត',
                'name' => 'Historical resort',
                'create_uid' => 1,
                'update_uid' => 1
            ],
            [
                'name_km' => 'រមណីយដ្ឋាន​ វប្បធម៌',
                'name' => 'Cultural resort',
                'create_uid' => 1,
                'update_uid' => 1
            ],
            [
                'name_km' => 'សៀមរាប',
                'name' => 'Siem Reap',
                'create_uid' => 1,
                'update_uid' => 1
            ],
            [
                'name_km' => 'រមណីយដ្ឋាន កែច្នៃ',
                'name' => 'Resort processing',
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
