<?php namespace Flarum\Tags\Listeners;

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
            'tags.discussion_tagged_post',
            'tags.added_tags',
            'tags.removed_tags',
            'tags.tag_new_discussion_title',
            'tags.edit_discussion_tags_title',
            'tags.edit_discussion_tags_link',
            'tags.discussion_tags_placeholder',
            'tags.confirm',
            'tags.more',
            'tags.tag_cloud_title',
            'tags.deleted'
        ]);
    }

    public function addRoutes(RegisterForumRoutes $event)
    {
        $event->get('/t/{slug}', 'tags.forum.tag');
        $event->get('/tags', 'tags.forum.tags');
    }
}
