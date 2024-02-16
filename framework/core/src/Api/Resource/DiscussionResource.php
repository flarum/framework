<?php

namespace Flarum\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Endpoint\Create;
use Flarum\Api\JsonApi;
use Flarum\Api\Schema;
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
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;

class DiscussionResource extends AbstractDatabaseResource
{
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
        $slugManager = resolve(SlugManager::class);
        $actor = $context->getActor();

        if (Arr::get($context->request->getQueryParams(), 'bySlug', false)) {
            $discussion = $slugManager->forResource(Discussion::class)->fromSlug($id, $actor);
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
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('title')
                ->requiredOnCreate()
                ->writable(function (Discussion $discussion, Context $context) {
                    return $context->endpoint instanceof Endpoint\Create
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
                    return resolve(SlugManager::class)->forResource(Discussion::class)->toSlug($discussion);
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
                    return $context->endpoint instanceof Endpoint\Update
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
                    return $context->endpoint instanceof Endpoint\Update;
                })
                ->set(function (Discussion $discussion, int $value, Context $context) {
                    if ($readNumber = Arr::get($context->body(), 'data.attributes.lastReadPostNumber')) {
                        $discussion->afterSave(function (Discussion $discussion) use ($readNumber, $context) {
                            resolve(Dispatcher::class)->dispatch(
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
                ->type('posts'),
            Schema\Relationship\ToOne::make('lastPostedUser')
                ->includable()
                ->type('users'),
            Schema\Relationship\ToOne::make('lastPost')
                ->includable()
                ->type('posts'),
            Schema\Relationship\ToMany::make('posts')
                ->withLinkage()
                ->includable()
                ->get(function (Discussion $discussion, Context $context) {
                    if ($context->endpoint instanceof Endpoint\Show) {
                        $actor = $context->getActor();

                        $limit = $context->endpoint->extractLimitValue($context, $context->endpoint->defaultExtracts($context));

                        if (($near = Arr::get($context->request->getQueryParams(), 'page.near')) > 1) {
                            $offset = resolve(PostRepository::class)->getIndexForNumber($discussion->id, $near, $actor);
                            $offset = max(0, $offset - $limit / 2);
                        } else {
                            $offset = $context->endpoint->extractOffsetValue($context, $context->endpoint->defaultExtracts($context));
                        }

                        $posts = $discussion->posts()
                            ->whereVisibleTo($actor)
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
                    }

                    return [];
                }),
            Schema\Relationship\ToOne::make('mostRelevantPost')
                ->visible(fn (Discussion $model, Context $context) => $context->endpoint instanceof Endpoint\Index)
                ->includable()
                ->type('posts'),
            Schema\Relationship\ToOne::make('hideUser')
                ->type('users'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('lastPostedAt'),
            SortColumn::make('commentCount'),
            SortColumn::make('createdAt'),
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
            resolve(Dispatcher::class)->dispatch(
                new ReadDiscussion($model->id, $actor, 1)
            );
        }

        return $model;
    }

    /** @param Discussion $model */
    protected function saveModel(Model $model, \Tobyz\JsonApiServer\Context $context): void
    {
        if ($context->endpoint instanceof Endpoint\Create) {
            $model->newQuery()->getConnection()->transaction(function () use ($model, $context) {
                $model->save();

                /**
                 * @var JsonApi $api
                 * @var Post $post
                 */

                $api = $context->api;

                // Now that the discussion has been created, we can add the first post.
                // We will do this by running the PostReply command.
                $post = $api->forResource(PostResource::class)
                    ->forEndpoint(Create::class)
                    ->withRequest($context->request)
                    ->execute([
                        'data' => [
                            'attributes' => [
                                'content' => $context->request->getParsedBody()['data']['attributes']['content'],
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

    protected function newSavingEvent(\Tobyz\JsonApiServer\Context $context, array $data): ?object
    {
        return new Saving($context->model, $context->getActor(), $data);
    }
}
