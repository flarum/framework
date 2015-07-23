<?php namespace Flarum\Mentions\Listeners;

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

        $event->forumBootstrapper('mentions/main');

        $event->forumTranslations([
            'mentions.reply_to_post',
            'mentions.post_mentioned_notification',
            'mentions.others',
            'mentions.user_mentioned_notification',
            'mentions.post_mentioned_by',
            'mentions.you',
            'mentions.reply_link',
            'mentions.notify_post_mentioned',
            'mentions.notify_user_mentioned'
        ]);
    }
}
