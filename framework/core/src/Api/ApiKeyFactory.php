<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => $this->faker->sha256,
            'allowed_ips' => null,
            'scopes' => null,
            'user_id' => null,
            'created_at' => Carbon::now(),
            'last_activity_at' => null,
        ];
    }
}
