<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscussionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'comment_count' => 1,
            'participant_count' => 1,
            'created_at' => Carbon::now(),
            'user_id' => User::factory(),
            'first_post_id' => null,
            'last_posted_at' => null,
            'last_posted_user_id' => null,
            'last_post_id' => null,
            'last_post_number' => null,
            'hidden_at' => null,
            'hidden_user_id' => null,
            'slug' => $this->faker->slug,
            'is_private' => 0
        ];
    }
}
