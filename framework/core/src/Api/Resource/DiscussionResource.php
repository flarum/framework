<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Bus\Dispatcher;
use Flarum\Discussion\Command\ReadDiscussion;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleting;
use Flarum\Discussion\Event\Saving;
use Flarum\Discussion\Event\Started;
use Flarum\Http\SlugManager;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * @extends AbstractDatabaseResource<Discussion>
 */
class DiscussionResource extends AbstractDatabaseResource
{
    public function __construct(
        protected Dispatcher $bus,
        protected SlugManager $slugManager,
        protected PostRepository $posts
    ) {
    }

    public function type(): string
    {
        return 'discussions';
    }

    public function model(): string
    {
        return Discussion::class;
    }

    public function scope(Builder $query, \Tobyz\JsonApiServer\Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function find(string $id, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $actor = $context->getActor();

        if (Arr::get($context->request->getQueryParams(), 'bySlug', false)) {
            $discussion = $this->slugManager->forResource(Discussion::class)->fromSlug($id, $actor);
        } else {
            $discussion = $this->query($context)->findOrFail($id);
        }

        return $discussion;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->can('startDiscussion')
                ->defaultInclude([
                    'posts',
                    'user',
                    'lastPostedUser',
                    'firstPost',
                    'lastPost'
                ]),
            Endpoint\Update::make()
                ->authenticated(),
            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete'),
            Endpoint\Show::make()
                ->defaultInclude([
                    'user',
                    'posts',
                    'posts.discussion',
                    'posts.user',
                    'posts.user.groups',
                    'posts.editedUser',
                    'posts.hiddenUser'
                ]),
            Endpoint\Index::make()
                ->defaultInclude([
                    'user',
                    'lastPostedUser',
                    'mostRelevantPost',
                    'mostRelevantPost.user'
                ])
                ->defaultSort('-lastPostedAt')
                ->eagerLoad('state')
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('title')
                ->requiredOnCreate()
                ->writable(function (Discussion $discussion, Context $context) {
                    return $context->creating()
                        || $context->getActor()->can('rename', $discussion);
                })
                ->minLength(3)
                ->maxLength(80),
            Schema\Str::make('content')
                ->writableOnCreate()
                ->requiredOnCreate()
                ->visible(false)
                ->maxLength(63000)
                // set nothing...
                ->set(fn () => null),
            Schema\Str::make('slug')
                ->get(function (Discussion $discussion) {
                    return $this->slugManager->forResource(Discussion::class)->toSlug($discussion);
                }),
            Schema\Integer::make('commentCount'),
            Schema\Integer::make('participantCount'),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('lastPostedAt'),
            Schema\Integer::make('lastPostNumber'),
            Schema\Boolean::make('canReply')
                ->get(function (Discussion $discussion, Context $context) {
                    return $context->getActor()->can('reply', $discussion);
                }),
            Schema\Boolean::make('canRename')
                ->get(function (Discussion $discussion, Context $context) {
                    return $context->getActor()->can('rename', $discussion);
                }),
            Schema\Boolean::make('canDelete')
                ->get(function (Discussion $discussion, Context $context) {
                    return $context->getActor()->can('delete', $discussion);
                }),
            Schema\Boolean::make('canHide')
                ->get(function (Discussion $discussion, Context $context) {
                    return $context->getActor()->can('hide', $discussion);
                }),
            Schema\Boolean::make('isHidden')
                ->visible(fn (Discussion $discussion) => $discussion->hidden_at !== null)
                ->writable(function (Discussion $discussion, Context $context) {
                    return $context->updating()
                        && $context->getActor()->can('hide', $discussion);
                })
                ->set(function (Discussion $discussion, bool $value, Context $context) {
                    if ($value) {
                        $discussion->hide($context->getActor());
                    } else {
                        $discussion->restore();
                    }
                }),
            Schema\DateTime::make('hiddenAt')
                ->visible(fn (Discussion $discussion) => $discussion->hidden_at !== null),
            Schema\DateTime::make('lastReadAt')
                ->visible(fn (Discussion $discussion) => $discussion->state !== null)
                ->get(function (Discussion $discussion) {
                    return $discussion->state->last_read_at;
                }),
            Schema\Integer::make('lastReadPostNumber')
                ->visible(fn (Discussion $discussion) => $discussion->state !== null)
                ->get(function (Discussion $discussion) {
                    return $discussion->state?->last_read_post_number;
                })
                ->writable(function (Discussion $discussion, Context $context) {
                    return $context->updating();
                })
                ->set(function (Discussion $discussion, int $value, Context $context) {
                    if ($readNumber = Arr::get($context->body(), 'data.attributes.lastReadPostNumber')) {
                        $discussion->afterSave(function (Discussion $discussion) use ($readNumber, $context) {
                            $this->bus->dispatch(
                                new ReadDiscussion($discussion->id, $context->getActor(), $readNumber)
                            );
                        });
                    }
                }),

            Schema\Relationship\ToOne::make('user')
                ->writableOnCreate()
                ->includable(),
            Schema\Relationship\ToOne::make('firstPost')
                ->includable()
                ->inverse('discussion')
                ->type('posts'),
            Schema\Relationship\ToOne::make('lastPostedUser')
                ->includable()
                ->type('users'),
            Schema\Relationship\ToOne::make('lastPost')
                ->includable()
                ->inverse('discussion')
                ->type('posts'),
            Schema\Relationship\ToMany::make('posts')
                ->withLinkage(function (Context $context) {
                    return $context->showing(self::class);
                })
                ->includable()
                // @todo: remove this, and send a second request from the frontend to /posts instead. Revert Serializer::addIncluded while you're at it.
                ->get(function (Discussion $discussion, Context $context) {
                    $showingDiscussion = $context->showing(self::class);

                    if (! $showingDiscussion) {
                        return fn () => $discussion->posts->all();
                    }

                    /** @var Endpoint\Show $endpoint */
                    $endpoint = $context->endpoint;

                    $actor = $context->getActor();

                    $limit = PostResource::$defaultLimit;

                    if (($near = Arr::get($context->request->getQueryParams(), 'page.near')) > 1) {
                        $offset = $this->posts->getIndexForNumber($discussion->id, $near, $actor);
                        $offset = max(0, $offset - $limit / 2);
                    } else {
                        $offset = $endpoint->extractOffsetValue($context, $endpoint->defaultExtracts($context));
                    }

                    /** @var Endpoint\Endpoint $endpoint */
                    $endpoint = $context->endpoint;

                    $posts = $discussion->posts()
                        ->whereVisibleTo($actor)
                        ->with($endpoint->getEagerLoadsFor('posts', $context))
                        ->with($endpoint->getWhereEagerLoadsFor('posts', $context))
                        ->orderBy('number')
                        ->skip($offset)
                        ->take($limit)
                        ->get();

                    /** @var Post $post */
                    foreach ($posts as $post) {
                        $post->setRelation('discussion', $discussion);
                    }

                    $allPosts = $discussion->posts()->whereVisibleTo($actor)->orderBy('number')->pluck('id')->all();
                    $loadedPosts = $posts->all();

                    array_splice($allPosts, $offset, $limit, $loadedPosts);

                    return $allPosts;
                }),
            Schema\Relationship\ToOne::make('mostRelevantPost')
                ->visible(fn (Discussion $model, Context $context) => $context->listing())
                ->includable()
                ->inverse('discussion')
                ->type('posts'),
            Schema\Relationship\ToOne::make('hideUser')
                ->type('users'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('lastPostedAt')
                ->descendingAlias('latest'),
            SortColumn::make('commentCount')
                ->descendingAlias('top'),
            SortColumn::make('createdAt')
                ->ascendingAlias('oldest')
                ->descendingAlias('newest'),
        ];
    }

    /** @param Discussion $model */
    public function creating(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $actor = $context->getActor();

        $model->created_at = Carbon::now();
        $model->user_id = $actor->id;

        $model->setRelation('user', $actor);

        $model->raise(new Started($model));

        return $model;
    }

    /** @param Discussion $model */
    public function created(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $actor = $context->getActor();

        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($model->id, $actor, 1)
            );
        }

        return $model;
    }

    /** @param Discussion $model */
    protected function saveModel(Model $model, \Tobyz\JsonApiServer\Context $context): void
    {
        if ($context->creating()) {
            $model->newQuery()->getConnection()->transaction(function () use ($model, $context) {
                $model->save();

                /** @var JsonApi $api */
                $api = $context->api;

                // Now that the discussion has been created, we can add the first post.
                // We will do this by running the PostReply command.
                /** @var Post $post */
                $post = $api->forResource(PostResource::class)
                    ->forEndpoint('create')
                    ->withRequest($context->request)
                    ->process([
                        'data' => [
                            'attributes' => [
                                'content' => Arr::get($context->body(), 'data.attributes.content'),
                            ],
                            'relationships' => [
                                'discussion' => [
                                    'data' => [
                                        'type' => 'discussions',
                                        'id' => (string) $model->id,
                                    ],
                                ],
                            ],
                        ],
                    ], ['isFirstPost' => true]);

                // Before we dispatch events, refresh our discussion instance's
                // attributes as posting the reply will have changed some of them (e.g.
                // last_time.)
                $model->setRawAttributes($post->discussion->getAttributes(), true);
                $model->setFirstPost($post);
                $model->setLastPost($post);

                $model->save();
            });
        }

        parent::saveModel($model, $context);
    }

    /** @param Discussion $model */
    public function deleting(object $model, \Tobyz\JsonApiServer\Context $context): void
    {
        $this->events->dispatch(
            new Deleting($model, $context->getActor(), [])
        );
    }

    public function saving(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $this->events->dispatch(
            new Saving($model, $context->getActor(), Arr::get($context->body(), 'data', []))
        );

        return $model;
    }
}
