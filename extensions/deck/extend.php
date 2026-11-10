<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck;

use Flarum\Api\Resource\ForumResource;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Post\Filter\PostSearcher;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Event\Saving;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->route('/deck', 'deck', Forum\Content\AssertCanUseDeck::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(Api\ForumResourceFields::class),

    (new Extend\User())
        ->registerPreference(DeckLayout::PREFERENCE_KEY, [DeckLayout::class, 'sanitize'])
        ->registerPreference(DeckLayout::SPLIT_PREFERENCE_KEY, [DeckLayout::class, 'sanitizeSplit']),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(DiscussionSearcher::class, Search\LastPostedAfterFilter::class)
        ->addFilter(PostSearcher::class, Search\AuthorGroupFilter::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\EnforceDeckPermissions::class),

    (new Extend\Settings())
        ->default(DeckLayout::POLL_INTERVAL_SETTING, 60)
        ->default(DeckLayout::MAX_COLUMNS_SETTING, 8),
];
