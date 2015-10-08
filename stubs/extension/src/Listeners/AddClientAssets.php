<?php namespace {{namespace}}\Listeners;

use Flarum\Event\ConfigureLocales;
use Flarum\Event\ConfigureClientView;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureLocales::class, [$this, 'addLocale']);
        $events->listen(ConfigureClientView::class, [$this, 'addAssets']);
    }

    public function addLocale(ConfigureLocales $event)
    {
        $event->addTranslations('en', __DIR__.'/../../locale/en.yml');
    }

    public function addAssets(ConfigureClientView $event)
    {
        $event->forumAssets([
            __DIR__.'/../../js/forum/dist/extension.js',
            __DIR__.'/../../less/forum/extension.less'
        ]);

        $event->forumBootstrapper('{{name}}/main');

        $event->forumTranslations([
            // '{{name}}.hello_world'
        ]);

        $event->adminAssets([
            __DIR__.'/../../js/admin/dist/extension.js',
            __DIR__.'/../../less/admin/extension.less'
        ]);

        $event->adminBootstrapper('{{name}}/main');

        $event->adminTranslations([
            // '{{name}}.hello_world'
        ]);
    }
}
