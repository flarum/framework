<?php

$factory('Flarum\Core\Discussions\Discussion', [
    'title' => $faker->sentence,
    'start_time' => $faker->dateTimeThisYear,
    'start_user_id' => 'factory:Flarum\Core\Users\User'
]);

$factory('Flarum\Core\Users\User', [
    'username' => $faker->userName,
    'email' => $faker->safeEmail,
    'password' => 'password',
    'join_time' => $faker->dateTimeThisYear,
    'time_zone' => $faker->timezone
]);