<?php namespace Flarum\Categories;

use Flarum\Support\ServiceProvider;
use Flarum\Api\Actions\Discussions\IndexAction as DiscussionsIndexAction;
use Flarum\Api\Actions\Discussions\ShowAction as DiscussionsShowAction;
use Illuminate\Contracts\Events\Dispatcher;

class CategoriesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Categories\Handlers\DiscussionMovedNotifier');
        $events->subscribe('Flarum\Categories\Handlers\CategoryPreloader');
        $events->subscribe('Flarum\Categories\Handlers\CategorySaver');

        $this->forumAssets([
            __DIR__.'/../js/dist/extension.js',
            __DIR__.'/../less/categories.less'
        ]);

        $this->postType('Flarum\Categories\DiscussionMovedPost');

        $this->discussionGambit('Flarum\Categories\CategoryGambit');

        $this->notificationType('Flarum\Categories\DiscussionMovedNotification', ['alert' => true]);

        $this->relationship('Flarum\Core\Models\Discussion', 'belongsTo', 'category', 'Flarum\Categories\Category');

        $this->serializeRelationship('Flarum\Api\Serializers\DiscussionSerializer', 'hasOne', 'category', 'Flarum\Categories\CategorySerializer');

        DiscussionsIndexAction::$include['category'] = true;
        DiscussionsShowAction::$include['category'] = true;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Categories\CategoryRepositoryInterface',
            'Flarum\Categories\EloquentCategoryRepository'
        );
    }
}
