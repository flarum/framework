<?php namespace Flarum\Sticky\Listeners;

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

        $event->forumBootstrapper('sticky/main');

        $event->forumTranslations([
            'sticky.discussion_stickied_notification',
            'sticky.discussion_stickied_post',
            'sticky.discussion_unstickied_post',
            'sticky.notify_discussion_stickied',
            'sticky.stickied',
            'sticky.sticky',
            'sticky.unsticky'
        ]);
    }
}
