<?php namespace Flarum\Core\Activity;

use Flarum\Core\Users\User;
use Flarum\Events\RegisterActivityTypes;
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
        $this->registerActivityTypes();

        $events = $this->app->make('events');
        $events->subscribe('Flarum\Core\Activity\Listeners\UserActivitySyncer');
    }

    /**
     * Register activity types.
     *
     * @return void
     */
    public function registerActivityTypes()
    {
        $blueprints = [
            'Flarum\Core\Activity\PostedBlueprint',
            'Flarum\Core\Activity\StartedDiscussionBlueprint',
            'Flarum\Core\Activity\JoinedBlueprint'
        ];

        event(new RegisterActivityTypes($blueprints));

        foreach ($blueprints as $blueprint) {
            Activity::setSubjectModel(
                $blueprint::getType(),
                $blueprint::getSubjectModel()
            );
        }
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
