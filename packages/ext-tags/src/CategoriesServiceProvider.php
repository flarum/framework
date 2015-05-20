<?php namespace Flarum\Categories;

use Flarum\Support\ServiceProvider;
use Flarum\Extend\EventSubscribers;
use Flarum\Extend\ForumAssets;
use Flarum\Extend\PostType;
use Flarum\Extend\DiscussionGambit;
use Flarum\Extend\NotificationType;
use Flarum\Extend\Relationship;
use Flarum\Extend\SerializeRelationship;
use Flarum\Extend\ApiInclude;
use Flarum\Extend\Permission;

class CategoriesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend(
            new EventSubscribers([
                'Flarum\Categories\Handlers\DiscussionMovedNotifier',
                'Flarum\Categories\Handlers\CategoryPreloader',
                'Flarum\Categories\Handlers\CategorySaver'
            ]),

            new ForumAssets([
                __DIR__.'/../js/dist/extension.js',
                __DIR__.'/../less/categories.less'
            ]),

            new PostType('Flarum\Categories\DiscussionMovedPost'),

            new DiscussionGambit('Flarum\Categories\CategoryGambit'),

            (new NotificationType('Flarum\Categories\DiscussionMovedNotification', 'Flarum\Api\Serializers\DiscussionBasicSerializer'))->enableByDefault('alert'),

            new Relationship('Flarum\Core\Models\Discussion', 'belongsTo', 'category', 'Flarum\Categories\Category'),

            new SerializeRelationship('Flarum\Api\Serializers\DiscussionSerializer', 'hasOne', 'category', 'Flarum\Categories\CategorySerializer'),

            new ApiInclude(['discussions.index', 'discussions.show'], 'category', true),

            (new Permission('discussion.move'))
                ->serialize()
                ->grant(function ($grant, $user) {
                    $grant->where('start_user_id', $user->id);
                    // @todo add limitations to time etc. according to a config setting
                })
        );
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
