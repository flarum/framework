<?php

namespace Flarum\Group;

use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_singular' => $singular = $this->faker->word,
            'name_plural' => $singular . '(s)',
            'color' => $this->faker->hexColor,
            'icon' => null,
            'is_hidden' => false,
        ];
    }
}
