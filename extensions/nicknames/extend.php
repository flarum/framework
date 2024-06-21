<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\Api\Resource;
use Flarum\Extend;
use Flarum\Nicknames\Access\UserPolicy;
use Flarum\Nicknames\Api\UserResourceFields;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Model(User::class))
        ->cast('nickname', 'string'),

    (new Extend\User())
        ->displayNameDriver('nickname', NicknameDriver::class),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(UserResourceFields::class)
        ->field('username', UserResourceFields::username(...)),

    (new Extend\Settings())
        ->default('flarum-nicknames.set_on_registration', true)
        ->default('flarum-nicknames.min', 1)
        ->default('flarum-nicknames.max', 150)
        ->default('display_name_driver', 'username')
        ->serializeToForum('displayNameDriver', 'display_name_driver')
        ->serializeToForum('setNicknameOnRegistration', 'flarum-nicknames.set_on_registration', 'boolval')
        ->serializeToForum('randomizeUsernameOnRegistration', 'flarum-nicknames.random_username', 'boolval'),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->setFulltext(UserSearcher::class, NicknameFullTextFilter::class),

    (new Extend\Policy())
        ->modelPolicy(User::class, UserPolicy::class),
];
