<?php namespace Flarum\Categories;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Notifications\Notifier;
use Flarum\Api\Actions\Discussions\IndexAction as DiscussionsIndexAction;
use Flarum\Api\Actions\Discussions\ShowAction as DiscussionsShowAction;

class CategoriesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events, Notifier $notifier)
    {
        $events->subscribe('Flarum\Categories\CategoriesHandler');
        $events->subscribe('Flarum\Categories\DiscussionMovedNotifier');

        // Add the category relationship to the Discussion model, and include
        // it in discussion-related API actions by default.
        Discussion::addRelationship('category', function ($model) {
            return $model->belongsTo('Flarum\Categories\Category', null, null, 'category');
        });
        DiscussionsIndexAction::$include['category'] = true;
        DiscussionsShowAction::$include['category'] = true;

        // Add a new post and notification type to represent a discussion
        // being moved from one category to another.
        Post::addType('Flarum\Categories\DiscussionMovedPost');

        $notifier->registerType('Flarum\Categories\DiscussionMovedNotification', ['alert' => true]);
    }

    public function register()
    {
        $this->app->bind(
            'Flarum\Categories\CategoryRepositoryInterface',
            'Flarum\Categories\EloquentCategoryRepository'
        );
    }
}
