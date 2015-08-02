<?php namespace Flarum\Tags;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function boot(Dispatcher $events)
    {
        Tag::setValidator($this->app->make('validator'));

        $events->subscribe('Flarum\Tags\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Tags\Listeners\AddModelRelationship');
        $events->subscribe('Flarum\Tags\Listeners\ConfigureDiscussionPermissions');
        $events->subscribe('Flarum\Tags\Listeners\ConfigureTagPermissions');
        $events->subscribe('Flarum\Tags\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Tags\Listeners\PersistData');
        $events->subscribe('Flarum\Tags\Listeners\LogDiscussionTagged');
        $events->subscribe('Flarum\Tags\Listeners\UpdateTagMetadata');
        $events->subscribe('Flarum\Tags\Listeners\AddTagGambit');
    }
}
