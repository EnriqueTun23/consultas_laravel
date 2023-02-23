<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        User::factory(15)->create();
        Tag::factory(30)->create();
        Category::factory(5)->create();
        Post::factory(50)->create()->each(function (Post $post) {
            $numberOfTags = rand(0, 5);
            if ($numberOfTags) {
                // PARA PODER  VINVULAR LOS TAGS CREADOS PREVIAMENTE
                $post->tags()->sync(Tag::all()->random($numberOfTags)->pluck('id'));
            }
        });
    }
}
