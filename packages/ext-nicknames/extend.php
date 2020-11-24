<?php

/*
 * This file is part of flarum/nickname.
 *
 * Copyright (c) 2020 Flarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\Event\ConfigureUserGambits;
use Flarum\Extend;
use Flarum\User\Event\Saving;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    (new Extend\User())
        ->displayNameDriver('nickname', NicknameDriver::class),

    (new Extend\ModelUrl(User::class))
        ->addSlugDriver('idOnly', IdOnlyUserSlugDriver::class),

    (new Extend\Event())
        ->listen(Saving::class, SaveNicknameToDatabase::class)
        ->listen(ConfigureUserGambits::class, SetUserNicknameGambit::class),

    new Extend\Locales(__DIR__ . '/resources/locale'),
];
