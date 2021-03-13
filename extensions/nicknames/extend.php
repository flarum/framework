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

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Extend;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\Saving;
use Flarum\User\UserValidator;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\User())
        ->displayNameDriver('nickname', NicknameDriver::class),

    (new Extend\Event())
        ->listen(Saving::class, SaveNicknameToDatabase::class)
        ->listen(ConfigureUserGambits::class, SetUserNicknameGambit::class),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('canEditOwnNickname', function ($serializer, $user) {
            $actor = $serializer->getActor();
            return $actor->id === $user->id && $serializer->getActor()->can('editOwnNickname', $user);
        }),

    (new Extend\Settings())
        ->serializeToForum('displayNameDriver', 'display_name_driver', null, 'username'),

    (new Extend\Validator(UserValidator::class))
        ->configure(AddNicknameValidation::class),

];
