<?php

$factory('Flarum\Core\Models\Discussion', [
    'title' => $faker->sentence,
    'start_time' => $faker->dateTimeThisYear,
    'start_user_id' => 'factory:Flarum\Core\Models\User'
]);

$factory('Flarum\Core\Models\User', [
    'username' => $faker->userName,
    'email' => $faker->safeEmail,
    'password' => 'password',
    'join_time' => $faker->dateTimeThisYear
]);
