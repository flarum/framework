<?php namespace Flarum\Subscriptions\Listeners;

use Flarum\Events\RegisterLocales;
use Flarum\Events\BuildClientView;
use Flarum\Events\RegisterForumRoutes;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterLocales::class, __CLASS__.'@addLocale');
        $events->listen(BuildClientView::class, __CLASS__.'@addAssets');
        $events->listen(RegisterForumRoutes::class, __CLASS__.'@addRoutes');
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

        $event->forumBootstrapper('subscriptions/main');

        $event->forumTranslations([
            'subscriptions.following',
            'subscriptions.ignoring',
            'subscriptions.follow',
            'subscriptions.unfollow',
            'subscriptions.ignore',
            'subscriptions.notify_new_post',
            'subscriptions.new_post_notification',
            'subscriptions.not_following',
            'subscriptions.not_following_description',
            'subscriptions.following_description',
            'subscriptions.ignoring_description',
            'subscriptions.unignore'
        ]);
    }

    public function addRoutes(RegisterForumRoutes $event)
    {
        $event->get('/following', 'flarum.forum.following');
    }
}
