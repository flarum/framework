<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Carbon\Carbon;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'user',
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'reason' => $this->faker->sentence,
            'reason_detail' => $this->faker->sentence,
            'created_at' => Carbon::now(),
        ];
    }
}
