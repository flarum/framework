<?php

use Flarum\Extend;
use Flarum\User\User;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-tags', fn () => [
            (new Extend\Model(User::class))
                ->cast('enabled_extension_cast', 'boolean'),
        ])
        ->whenExtensionDisabled('flarum-tags', function () {
            return [
                (new Extend\Model(User::class))
                    ->cast('disabled_extension_cast', 'boolean'),
            ];
        })
        ->whenSetting('theme', 'dark', fn () => [
            (new Extend\Model(User::class))
                ->cast('setting_cast', 'boolean'),
        ])
        ->when(true, fn () => [
            (new Extend\Model(User::class))
                ->cast('generic_condition_cast', 'boolean'),
        ]),
];
