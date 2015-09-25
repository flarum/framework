<?php

// See http://flarum.org/docs/localization to learn how this file works.

return [
    'plural' => function ($count) {
        return $count == 1 ? 'one' : 'other';
    }
];
