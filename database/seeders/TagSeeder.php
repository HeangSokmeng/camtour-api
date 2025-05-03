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
            ['id' => 1, 'name' => 'sea', 'create_uid'=>1, 'update_uid'=>1],
            ['id' => 2, 'name' => 'angkor', 'create_uid'=>1, 'update_uid'=>1],
            ['id' => 3, 'name' => 'temple', 'create_uid'=>1, 'update_uid'=>1],
            ['id' => 4, 'name' => 'mountian', 'create_uid'=>1, 'update_uid'=>1],
            ['id' => 5, 'name' => 'sunset', 'create_uid'=>1, 'update_uid'=>1],
            ['id' => 6, 'name' => 'camping', 'create_uid'=>1, 'update_uid'=>1],
        ];

        // store new tag
        foreach ($dataTags as $dataTag) {
            $tag     = new Tag($dataTag);
            $tag->id = $dataTag['id'];
            $tag->save();
        }
    }
}
