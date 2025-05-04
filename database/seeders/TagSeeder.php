<?php
namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        // setup default
        $dataTags = [
            ['name' => 'sea', 'create_uid'=>1, 'update_uid'=>1],
            ['name' => 'angkor', 'create_uid'=>1, 'update_uid'=>1],
            ['name' => 'temple', 'create_uid'=>1, 'update_uid'=>1],
            ['name' => 'mountian', 'create_uid'=>1, 'update_uid'=>1],
            ['name' => 'sunset', 'create_uid'=>1, 'update_uid'=>1],
            ['name' => 'camping', 'create_uid'=>1, 'update_uid'=>1],
        ];

        // store new tag
        foreach ($dataTags as $dataTag) {
            $tag   = new Tag($dataTag);
            $tag->save();
        }
    }
}
