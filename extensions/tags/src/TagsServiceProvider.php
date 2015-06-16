<?php namespace Flarum\Tags;

use Flarum\Support\ServiceProvider;
use Flarum\Extend\ForumAssets;
use Flarum\Extend\EventSubscribers;
use Flarum\Extend\Relationship;
use Flarum\Extend\SerializeRelationship;
use Flarum\Extend\ApiInclude;
use Flarum\Extend\ApiLink;
use Flarum\Extend\Permission;
use Flarum\Extend\DiscussionGambit;
use Flarum\Extend\PostType;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;

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
                'Flarum\Tags\Handlers\TagSaver',
                'Flarum\Tags\Handlers\TagLoader'
            ]),

            new Relationship('Flarum\Core\Models\Discussion', 'tags', function ($model) {
                return $model->belongsToMany('Flarum\Tags\Tag', 'discussions_tags');
            }),

            new SerializeRelationship('Flarum\Api\Serializers\DiscussionBasicSerializer', 'hasMany', 'tags', 'Flarum\Tags\TagSerializer'),

            new ApiInclude(['discussions.index', 'discussions.show'], 'tags', true),

            new SerializeRelationship('Flarum\Api\Serializers\ForumSerializer', 'hasMany', 'tags', 'Flarum\Tags\TagSerializer'),

            new ApiInclude(['forum.show'], ['tags', 'tags.lastDiscussion'], true),
            new ApiLink(['forum.show'], ['tags.parent'], true),

            (new Permission('discussion.tag'))
                ->serialize(),
                // ->grant(function ($grant, $user) {
                //     $grant->where('start_user_id', $user->id);
                //     // @todo add limitations to time etc. according to a config setting
                // }),

            new DiscussionGambit('Flarum\Tags\TagGambit'),

            new PostType('Flarum\Tags\DiscussionTaggedPost')
        ]);

        Tag::scopeVisible(function ($query, User $user) {
            $query->whereIn('id', $this->getTagsWithPermission($user, 'view'));
        });

        Tag::allow('startDiscussion', function (Tag $tag, User $user) {
            if (! $tag->is_restricted || $user->hasPermission('tag'.$tag->id.'.startDiscussion')) {
                return true;
            }
        });

        Discussion::scopeVisible(function ($query, User $user) {
            $query->whereNotExists(function ($query) use ($user) {
                return $query->select(app('db')->raw(1))
                    ->from('discussions_tags')
                    ->whereNotIn('tag_id', $this->getTagsWithPermission($user, 'view'))
                    ->whereRaw('discussion_id = discussions.id');
            });
        });

        Discussion::allow('*', function (Discussion $discussion, User $user, $action) {
            $tags = $discussion->getRelation('tags');

            if (! count($tags)) {
                return;
            }

            $restricted = true;

            // If the discussion has a tag that has been restricted, and the user
            // has this permission for that tag, then they are allowed. If the
            // discussion only has tags that have been restricted, then the user
            // *must* have permission for at least one of them. Otherwise, inherit
            // global permissions.
            foreach ($tags as $tag) {
                if ($tag->is_restricted) {
                    if ($user->hasPermission('tag'.$tag->id.'.discussion.'.$action)) {
                        return true;
                    }
                } else {
                    $restricted = false;
                }
            }

            if ($restricted) {
                return false;
            }
        });

        Post::allow('*', function (Post $post, User $user, $action) {
            return $post->discussion->can($user, $action.'Posts');
        });
    }

    protected function getTagsWithPermission($user, $permission) {
        static $tags;
        if (!$tags) $tags = Tag::all();

        $ids = [];
        foreach ($tags as $tag) {
            if (! $tag->is_restricted || $user->hasPermission('tag'.$tag->id.'.'.$permission)) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
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
