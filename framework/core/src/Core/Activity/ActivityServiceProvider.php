<?php namespace Flarum\Core\Activity;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class ActivityServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            (new Extend\EventSubscriber('Flarum\Core\Handlers\Events\UserActivitySyncer')),

            (new Extend\ActivityType('Flarum\Core\Activity\PostedActivity'))
                ->subjectSerializer('Flarum\Api\Serializers\PostBasicSerializer'),

            (new Extend\ActivityType('Flarum\Core\Activity\StartedDiscussionActivity'))
                ->subjectSerializer('Flarum\Api\Serializers\PostBasicSerializer'),

            (new Extend\ActivityType('Flarum\Core\Activity\JoinedActivity'))
                ->subjectSerializer('Flarum\Api\Serializers\UserBasicSerializer')
        ]);
    }

    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Repositories\ActivityRepositoryInterface',
            'Flarum\Core\Repositories\EloquentActivityRepository'
        );
    }
}
