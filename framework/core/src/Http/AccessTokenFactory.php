<?php

namespace Flarum\Http;

use Carbon\Carbon;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccessTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'token' => Str::random(40),
            'user_id' => User::factory(),
            'last_activity_at' => null,
            'type' => 'developer',
            'title' => $this->faker->sentence,
            'last_ip_address' => null,
            'last_user_agent' => null,
            'created_at' => Carbon::now(),
        ];
    }
}
