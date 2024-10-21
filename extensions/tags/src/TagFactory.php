<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'color' => $this->faker->hexColor,
            'background_path' => null,
            'background_mode' => null,
            'position' => 0,
            'parent_id' => null,
            'default_sort' => null,
            'is_restricted' => false,
            'is_hidden' => false,
            'discussion_count' => 0,
            'last_posted_at' => null,
            'last_posted_discussion_id' => null,
            'last_posted_user_id' => null,
            'icon' => null,
            'created_at' => Carbon::now(),
        ];
    }
}
