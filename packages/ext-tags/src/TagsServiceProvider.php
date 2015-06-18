<?php namespace Flarum\Tags;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\User;

class TagsServiceProvider extends ServiceProvider
{
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

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            (new Extend\ForumClient())
                ->assets([
                    __DIR__.'/../js/dist/extension.js',
                    __DIR__.'/../less/extension.less'
                ]),

            (new Extend\Model('Flarum\Tags\Tag'))
                // Hide tags that the user doesn't have permission to see.
                ->scopeVisible(function ($query, User $user) {
                    $query->whereIn('id', Tag::getVisibleTo($user));
                })

                // Allow the user to start discussions in tags which aren't
                // restricted, or for which the user has explicitly been granted
                // permission.
                ->allow('startDiscussion', function (Tag $tag, User $user) {
                    if (! $tag->is_restricted || $user->hasPermission('tag'.$tag->id.'.startDiscussion')) {
                        return true;
                    }
                }),

            // Expose the complete tag list to clients by adding it as a
            // relationship to the /api/forum endpoint. Since the Forum model
            // doesn't actually have a tags relationship, we will manually
            // load and assign the tags data to it using an event listener.
            (new Extend\ApiSerializer('Flarum\Api\Serializers\ForumSerializer'))
                ->hasMany('tags', 'Flarum\Tags\TagSerializer'),

            (new Extend\ApiAction('Flarum\Api\Actions\Forum\ShowAction'))
                ->addInclude('tags')
                ->addInclude('tags.lastDiscussion')
                ->addLink('tags.parent'),

            new Extend\EventSubscriber('Flarum\Tags\Handlers\TagLoader'),

            // Extend the Discussion model and API: add the tags relationship
            // and modify permissions.
            (new Extend\Model('Flarum\Core\Models\Discussion'))
                ->belongsToMany('tags', 'Flarum\Tags\Tag', 'discussions_tags')

                // Hide discussions which have tags that the user is not allowed
                // to see.
                ->scopeVisible(function ($query, User $user) {
                    $query->whereNotExists(function ($query) use ($user) {
                        return $query->select(app('db')->raw(1))
                            ->from('discussions_tags')
                            ->whereNotIn('tag_id', Tag::getVisibleTo($user))
                            ->whereRaw('discussion_id = discussions.id');
                    });
                })

                // Wrap all discussion permission checks with some logic
                // pertaining to the discussion's tags. If the discussion has a
                // tag that has been restricted, and the user has this
                // permission for that tag, then they are allowed. If the
                // discussion only has tags that have been restricted, then the
                // user *must* have permission for at least one of them.
                ->allow('*', function (Discussion $discussion, User $user, $action) {
                    $tags = $discussion->getRelation('tags');

                    if (count($tags)) {
                        $restricted = true;

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
                    }
                }),

            (new Extend\ApiSerializer('Flarum\Api\Serializers\DiscussionBasicSerializer'))
                ->hasMany('tags', 'Flarum\Tags\TagSerializer')
                ->attributes(function (&$attributes, $discussion, $user) {
                    $attributes['canTag'] = $discussion->can($user, 'tag');
                }),

            (new Extend\ApiAction([
                'Flarum\Api\Actions\Discussions\IndexAction',
                'Flarum\Api\Actions\Discussions\ShowAction'
            ]))
                ->addInclude('tags'),

            // Add an event subscriber so that tags data is persisted when
            // saving a discussion.
            new Extend\EventSubscriber('Flarum\Tags\Handlers\TagSaver'),

            // Add a gambit that allows filtering discussions by tag(s).
            new Extend\DiscussionGambit('Flarum\Tags\TagGambit'),

            // Add a new post type which indicates when a discussion's tags were
            // changed.
            new Extend\PostType('Flarum\Tags\DiscussionTaggedPost'),
            new Extend\EventSubscriber('Flarum\Tags\Handlers\DiscussionTaggedNotifier')
        ]);
    }
}
