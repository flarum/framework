<?php namespace Flarum\Tags;

use Flarum\Support\ServiceProvider;
use Flarum\Extend\ForumAssets;
use Flarum\Extend\EventSubscribers;
use Flarum\Extend\Relationship;
use Flarum\Extend\SerializeRelationship;
use Flarum\Extend\ApiInclude;
use Flarum\Extend\Permission;
use Flarum\Extend\DiscussionGambit;
use Flarum\Extend\PostType;

class TagsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            new ForumAssets([
                __DIR__.'/../js/dist/extension.js',
                __DIR__.'/../less/extension.less'
            ]),

            new EventSubscribers([
                'Flarum\Tags\Handlers\DiscussionTaggedNotifier',
                'Flarum\Tags\Handlers\TagSaver'
            ]),

            new Relationship('Flarum\Core\Models\Discussion', 'tags', function ($model) {
                return $model->belongsToMany('Flarum\Tags\Tag', 'discussions_tags');
            }),

            new SerializeRelationship('Flarum\Api\Serializers\DiscussionBasicSerializer', 'hasMany', 'tags', 'Flarum\Tags\TagSerializer'),

            new ApiInclude(['discussions.index', 'discussions.show'], 'tags', true),

            new Relationship('Flarum\Core\Models\Forum', 'tags', function ($model) {
                return Tag::query();
            }),

            new SerializeRelationship('Flarum\Api\Serializers\ForumSerializer', 'hasMany', 'tags', 'Flarum\Tags\TagSerializer'),

            new ApiInclude(['forum.show'], ['tags', 'tags.parent', 'tags.lastDiscussion'], true),

            (new Permission('discussion.tag'))
                ->serialize()
                ->grant(function ($grant, $user) {
                    $grant->where('start_user_id', $user->id);
                    // @todo add limitations to time etc. according to a config setting
                }),

            new DiscussionGambit('Flarum\Tags\TagGambit'),

            new PostType('Flarum\Tags\DiscussionTaggedPost')
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
            'Flarum\Tags\TagRepositoryInterface',
            'Flarum\Tags\EloquentTagRepository'
        );
    }
}
