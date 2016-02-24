<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Settings\SettingsRepositoryInterface;

return [
    'up' => function (SettingsRepositoryInterface $settings) {
        $settings->set('flarum-tags.max_primary_tags', '1');
        $settings->set('flarum-tags.min_primary_tags', '1');
        $settings->set('flarum-tags.max_secondary_tags', '3');
        $settings->set('flarum-tags.min_secondary_tags', '0');
    },

    'down' => function (SettingsRepositoryInterface $settings) {
        $settings->delete('flarum-tags.max_primary_tags');
        $settings->delete('flarum-tags.max_secondary_tags');
        $settings->delete('flarum-tags.min_primary_tags');
        $settings->delete('flarum-tags.min_secondary_tags');
    }
];
