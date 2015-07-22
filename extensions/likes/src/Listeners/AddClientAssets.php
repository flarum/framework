<?php namespace Flarum\Likes\Listeners;

use Flarum\Events\RegisterLocales;
use Flarum\Events\BuildClientView;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterLocales::class, __CLASS__.'@addLocale');
        $events->listen(BuildClientView::class, __CLASS__.'@addAssets');
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

        $event->forumBootstrapper('likes/main');

        $event->forumTranslations([
            'likes.post_liked_notification',
            'likes.post_likes_modal_title',
            'likes.post_liked_by_self',
            'likes.post_liked_by',
            'likes.unlike_action',
            'likes.like_action',
            'likes.notify_post_liked',
            'likes.others'
        ]);
    }
}
