<?php namespace Flarum\Lock\Listeners;

use Flarum\Events\RegisterLocales;
use Flarum\Events\BuildClientView;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterLocales::class, [$this, 'addLocale']);
        $events->listen(BuildClientView::class, [$this, 'addAssets']);
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

        $event->forumBootstrapper('lock/main');

        $event->forumTranslations([
            'lock.discussion_locked_notification',
            'lock.discussion_locked_post',
            'lock.discussion_unlocked_post',
            'lock.notify_discussion_locked',
            'lock.locked',
            'lock.lock',
            'lock.unlock'
        ]);
    }
}
