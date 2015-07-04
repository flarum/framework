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
            (new Extend\EventSubscriber('Flarum\Core\Activity\Listeners\UserActivitySyncer')),

            (new Extend\ActivityType('Flarum\Core\Activity\PostedBlueprint'))
                ->subjectSerializer('Flarum\Api\Serializers\PostBasicSerializer'),

            (new Extend\ActivityType('Flarum\Core\Activity\StartedDiscussionBlueprint'))
                ->subjectSerializer('Flarum\Api\Serializers\PostBasicSerializer'),

            (new Extend\ActivityType('Flarum\Core\Activity\JoinedBlueprint'))
                ->subjectSerializer('Flarum\Api\Serializers\UserBasicSerializer')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Activity\ActivityRepositoryInterface',
            'Flarum\Core\Activity\EloquentActivityRepository'
        );
    }
}
