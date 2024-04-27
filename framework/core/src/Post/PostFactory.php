<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'discussion_id' => Discussion::factory(),
            'number' => null,
            'created_at' => Carbon::now(),
            'user_id' => User::factory(),
            'type' => CommentPost::$type,
            'content' => $this->faker->paragraph,
            'edited_at' => null,
            'edited_user_id' => null,
            'hidden_at' => null,
            'hidden_user_id' => null,
            'ip_address' => null,
            'is_private' => 0
        ];
    }
}
