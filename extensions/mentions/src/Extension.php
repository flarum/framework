<?php namespace Flarum\Mentions;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function boot(Dispatcher $events)
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'mentions');

        $events->subscribe('Flarum\Mentions\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Mentions\Listeners\AddModelRelationships');
        $events->subscribe('Flarum\Mentions\Listeners\AddApiRelationships');
        $events->subscribe('Flarum\Mentions\Listeners\AddUserMentionsFormatter');
        $events->subscribe('Flarum\Mentions\Listeners\AddPostMentionsFormatter');
        $events->subscribe('Flarum\Mentions\Listeners\UpdateUserMentionsMetadata');
        $events->subscribe('Flarum\Mentions\Listeners\UpdatePostMentionsMetadata');
    }
}
