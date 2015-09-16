<?php namespace Flarum\Embed\Listeners;

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
        $events->listen(RegisterForumRoutes::class, [$this, 'addEmbedRoute']);
    }

    public function addLocale(RegisterLocales $event)
    {
        $event->addTranslations('en', __DIR__.'/../../locale/en.yml');
    }

    public function addAssets(BuildClientView $event)
    {

    }

    public function addEmbedRoute(RegisterForumRoutes $event)
    {
        $event->get('/embed/{id:\d+}', 'embed.discussion', 'Flarum\Embed\ClientAction');
    }
}
