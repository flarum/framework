<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => $this->faker->userName,
            'email' => $this->faker->safeEmail,
            'is_email_confirmed' => 1,
            'password' => $this->faker->password,
            'avatar_url' => $this->faker->imageUrl,
            'preferences' => [],
            'joined_at' => null,
            'last_seen_at' => null,
            'marked_all_as_read_at' => null,
            'read_notifications_at' => null,
            'discussion_count' => 0,
            'comment_count' => 0,
        ];
    }
}
