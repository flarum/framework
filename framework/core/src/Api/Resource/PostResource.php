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
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Bus\Dispatcher;
use Flarum\Discussion\Command\ReadDiscussion;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ErrorHandling\LogReporter;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Deleting;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Exception\BadRequestException;

/**
 * @extends AbstractDatabaseResource<Post>
 */
class PostResource extends AbstractDatabaseResource
{
    public static int $defaultLimit = 20;

    public function __construct(
        protected PostRepository $posts,
        protected TranslatorInterface $translator,
        protected LogReporter $log,
        protected Dispatcher $bus
    ) {
    }

    public function type(): string
    {
        return 'posts';
    }

    public function model(): string
    {
        return Post::class;
    }

    public function scope(Builder $query, \Tobyz\JsonApiServer\Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    /** @inheritDoc */
    public function newModel(\Tobyz\JsonApiServer\Context $context): object
    {
        if ($context->creating(self::class)) {
            $post = new CommentPost();

            $post->user_id = $context->getActor()->id;
            $post->ip_address = $context->request->getAttribute('ipAddress');

            return $post;
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->visible(function (Context $context): bool {
                    $discussionId = (int) Arr::get($context->body(), 'data.relationships.discussion.data.id');

                    // Make sure the user has permission to reply to this discussion. First,
                    // make sure the discussion exists and that the user has permission to
                    // view it; if not, fail with a ModelNotFound exception so we don't give
                    // away the existence of the discussion. If the user is allowed to view
                    // it, check if they have permission to reply.
                    $discussion = Discussion::query()
                        ->whereVisibleTo($context->getActor())
                        ->findOrFail($discussionId);

                    // If this is the first post in the discussion, it's technically not a
                    // "reply", so we won't check for that permission.
                    if (! $context->internal('isFirstPost')) {
                        return $context->getActor()->can('reply', $discussion);
                    }

                    return true;
                })
                ->defaultInclude([
                    'user',
                    'discussion',
                    'discussion.posts',
                    'discussion.lastPostedUser'
                ]),
            Endpoint\Update::make()
                ->authenticated()
                ->defaultInclude([
                    'editedUser',
                    'discussion'
                ]),
            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete'),
            Endpoint\Show::make()
                ->defaultInclude([
                    'user',
                    'user.groups',
                    'editedUser',
                    'hiddenUser',
                    'discussion'
                ]),
            Endpoint\Index::make()
                ->extractOffset(function (Context $context, array $defaultExtracts): int {
                    $queryParams = $context->request->getQueryParams();

                    if (($near = Arr::get($queryParams, 'page.near')) > 1) {
                        $sort = $defaultExtracts['sort'];
                        $filter = $defaultExtracts['filter'];

                        if (count($filter) > 1 || ! isset($filter['discussion']) || $sort) {
                            throw new BadRequestException(
                                'You can only use page[near] with filter[discussion] and the default sort order'
                            );
                        }

                        $limit = $defaultExtracts['limit'];
                        $offset = $this->posts->getIndexForNumber((int) $filter['discussion'], $near, $context->getActor());

                        return max(0, $offset - $limit / 2);
                    }

                    return $defaultExtracts['offset'];
                })
                ->defaultInclude([
                    'user',
                    'user.groups',
                    'editedUser',
                    'hiddenUser',
                    'discussion'
                ])
                ->paginate(static::$defaultLimit),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Integer::make('number'),
            Schema\DateTime::make('createdAt')
                ->writable(function (Post $post, Context $context) {
                    return $context->creating()
                        && $context->getActor()->isAdmin();
                })
                ->default(fn () => Carbon::now()),
            Schema\Str::make('contentType')
                ->property('type'),

            Schema\Str::make('content')
                ->requiredOnCreate()
                ->writable(function (Post $post, Context $context) {
                    return $context->creating() || (
                        $post instanceof CommentPost
                        && $context->updating()
                        && $context->getActor()->can('edit', $post)
                    );
                })
                ->maxLength(63000) // 65535 is without the text formatter XML generated after parsing. So we use 63000 to try being safer.
                ->visible(function (Post $post, Context $context) {
                    return ! ($post instanceof CommentPost)
                        || $context->getActor()->can('edit', $post);
                })
                ->set(function (Post $post, string $value, Context $context) {
                    if ($post instanceof CommentPost) {
                        if ($context->creating()) {
                            $post->setContentAttribute($value, $context->getActor());
                        } elseif ($context->updating()) {
                            $post->revise($value, $context->getActor());
                        }
                    }
                })
                ->serialize(function (null|string|array $value, Context $context) {
                    /**
                     * Prevent the string type from trying to convert array content (for event posts) to a string.
                     * @var Schema\Str $field
                     */
                    $field = $context->field;
                    $field->type = null;

                    return $value;
                }),
            Schema\Str::make('contentHtml')
                ->visible(function (Post $post) {
                    return $post instanceof CommentPost;
                })
                ->get(function (CommentPost $post, Context $context) {
                    try {
                        $rendered = $post->formatContent($context->request);
                        $post->setAttribute('renderFailed', false);
                    } catch (\Exception $e) {
                        $rendered = $this->translator->trans('core.lib.error.render_failed_message');
                        $this->log->report($e);
                        $post->setAttribute('renderFailed', true);
                    }

                    return $rendered;
                }),
            Schema\Boolean::make('renderFailed')
                ->visible(function (Post $post) {
                    return $post instanceof CommentPost;
                }),

            Schema\Str::make('ipAddress')
                ->visible(function (Post $post, Context $context) {
                    return $post instanceof CommentPost
                        && $context->getActor()->can('viewIps', $post);
                }),
            Schema\DateTime::make('editedAt'),
            Schema\Boolean::make('isHidden')
                ->visible(fn (Post $post) => $post->hidden_at !== null)
                ->writable(function (Post $post, Context $context) {
                    return $context->updating()
                        && $context->getActor()->can('hide', $post);
                })
                ->set(function (Post $post, bool $value, Context $context) {
                    if ($post instanceof CommentPost) {
                        if ($value) {
                            $post->hide($context->getActor());
                        } else {
                            $post->restore();
                        }
                    }
                }),
            Schema\DateTime::make('hiddenAt')
                ->visible(fn (Post $post) => $post->hidden_at !== null),

            Schema\Boolean::make('canEdit')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('edit', $post)),
            Schema\Boolean::make('canDelete')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('delete', $post)),
            Schema\Boolean::make('canHide')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('hide', $post)),

            Schema\Relationship\ToOne::make('user')
                ->includable(),
            Schema\Relationship\ToOne::make('discussion')
                ->includable()
                ->writableOnCreate(),
            Schema\Relationship\ToOne::make('editedUser')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('hiddenUser')
                ->type('users')
                ->includable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('number'),
            SortColumn::make('createdAt'),
        ];
    }

    /** @param Post $model */
    public function created(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $actor = $context->getActor();

        // After replying, we assume that the user has seen all of the posts
        // in the discussion; thus, we will mark the discussion as read if
        // they are logged in.
        if ($actor->exists) {
            $this->bus->dispatch(
                new ReadDiscussion($model->discussion_id, $actor, $model->number)
            );
        }

        return $model;
    }

    /** @param Post $model */
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
