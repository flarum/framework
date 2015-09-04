<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listeners;

use Flarum\Events\RegisterLocales;
use Flarum\Events\BuildClientView;
use Flarum\Events\RegisterForumRoutes;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterLocales::class, [$this, 'addLocale']);
        $events->listen(BuildClientView::class, [$this, 'addAssets']);
        $events->listen(RegisterForumRoutes::class, [$this, 'addRoutes']);
    }

    public function addLocale(RegisterLocales $event)
    {
        $event->addTranslations('en', __DIR__.'/../../locale/en.yml');
    }

    public function addAssets(BuildClientView $event)
    {
        $event->forumAssets([
            __DIR__.'/../../js/forum/dist/extension.js',
            __DIR__.'/../../less/forum/extension.less'
        ]);

        $event->forumBootstrapper('tags/main');

        $event->forumTranslations([
            'tags.tags',
            'tags.discussion_tagged_post',
            'tags.added_tags',
            'tags.removed_tags',
            'tags.tag_new_discussion_title',
            'tags.tag_new_discussion_link',
            'tags.edit_discussion_tags_title',
            'tags.edit_discussion_tags_link',
            'tags.choose_primary_tags',
            'tags.choose_secondary_tags',
            'tags.confirm',
            'tags.more',
            'tags.deleted'
        ]);

        $event->adminAssets([
            __DIR__.'/../../js/admin/dist/extension.js',
            __DIR__.'/../../less/admin/extension.less'
        ]);

        $event->adminBootstrapper('tags/main');
    }

    public function addRoutes(RegisterForumRoutes $event)
    {
        $event->get('/t/{slug}', 'tags.forum.tag');
        $event->get('/tags', 'tags.forum.tags');
    }
}
