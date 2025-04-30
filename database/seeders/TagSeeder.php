<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        // setup default
        $dataTags = [
            ['id' => 1, 'name' => 'sea'],
            ['id' => 2, 'name' => 'angkor'],
            ['id' => 3, 'name' => 'temple'],
            ['id' => 4, 'name' => 'mountian'],
            ['id' => 5, 'name' => 'sunset'],
            ['id' => 6, 'name' => 'camping'],
        ];

        // store new tag
        foreach ($dataTags as $dataTag) {
            $tag = new Tag($dataTag);
            $tag->id = $dataTag['id'];
            $tag->save();
        }
    }
}
