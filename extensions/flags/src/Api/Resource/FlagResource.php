<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Flags\Event\Created;
use Flarum\Flags\Event\UserFlagCreated;
use Flarum\Flags\Flag;
use Flarum\Flags\UserFlagger;
use Flarum\Foundation\ValidationException;
use Flarum\Http\Exception\InvalidParameterException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Flag>
 */
class FlagResource extends AbstractDatabaseResource
{
    public function __construct(
        protected PostRepository $posts,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected UserFlagger $userFlagger,
    ) {
    }

    public function type(): string
    {
        return 'flags';
    }

    public function model(): string
    {
        return Flag::class;
    }

    public function query(Context $context): object
    {
        if ($context->listing(self::class)) {
            $query = Flag::query()->whenPgSql(
                fn (Builder $query) => $query->distinct(['post_id', 'target_user_id'])->orderBy('post_id')->orderBy('target_user_id')->orderBy('created_at', 'desc'),
                else: fn (Builder $query) => $query->groupBy('post_id', 'target_user_id')
            );

            $this->scope($query, $context);

            return $query;
        }

        return parent::query($context);
    }

    /**
     * @param Builder<Flag> $query
     * @param FlarumContext $context
     */
    public function count(object $query, Context $context): ?int
    {
        // DISTINCT ON selects one report per target on PostgreSQL, but its
        // target columns cannot be reused in COUNT(DISTINCT col1, col2).
        // Count a grouped subquery so null post/account targets remain distinct.
        $query = (clone $query)->whenPgSql(
            fn (Builder $query) => $query->distinct(false)
                ->select(['post_id', 'target_user_id'])
                ->groupBy('post_id', 'target_user_id'),
            else: fn (Builder $query) => $query
        );

        return parent::count($query, $context);
    }

    public function scope(Builder $query, Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function newModel(Context $context): object
    {
        if ($context->creating(self::class)) {
            $postId = Arr::get($context->body(), 'data.relationships.post.data.id');
            $userId = Arr::get($context->body(), 'data.relationships.targetUser.data.id');

            if (($postId === null) === ($userId === null)) {
                throw new ValidationException([], ['target' => 'Exactly one post or targetUser relationship is required.']);
            }

            $identity = [
                'post_id' => $postId === null ? null : (int) $postId,
                'target_user_id' => $userId === null ? null : (int) $userId,
                'user_id' => $context->getActor()->id
            ];
            if ($userId !== null) {
                $identity['type'] = 'user';
            }

            return Flag::query()->firstOrNew($identity, [
                'type' => 'user',
            ]);
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->defaultInclude(['post', 'post.flags', 'targetUser', 'user']),
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'post', 'post.user', 'post.discussion', 'targetUser'])
                // The included discussions and users are serialized like any
                // others, so they need the relations their own resources
                // eager load: the actor's discussion state, and group
                // memberships for permission checks. Without this each
                // flag's discussion reads `discussion_user` on its own.
                ->eagerLoad([
                    'post.discussion.state',
                    'post.user.groups',
                    'user.groups',
                    'targetUser.groups',
                ])
                ->defaultSort('-createdAt')
                ->paginate()
                ->after(function (FlarumContext $context, $data) {
                    $actor = $context->getActor();

                    $actor->read_flags_at = Carbon::now();
                    $actor->save();

                    return $data;
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('type'),
            Schema\Str::make('reason')
                ->writableOnCreate()
                ->nullable()
                ->maxLength(255)
                ->requiredOnCreateWithout(['reasonDetail'])
                ->validationMessages([
                    'reason.required_without' => $this->translator->trans('flarum-flags.forum.flag_post.reason_missing_message'),
                ]),
            Schema\Str::make('reasonDetail')
                ->writableOnCreate()
                ->nullable()
                ->maxLength(2000)
                ->requiredOnCreateWithout(['reason'])
                ->validationMessages([
                    'reasonDetail.required_without' => $this->translator->trans('flarum-flags.forum.flag_post.reason_missing_message'),
                ]),
            Schema\DateTime::make('createdAt'),

            Schema\Relationship\ToOne::make('post')
                ->includable()
                ->writable(fn (Flag $flag, FlarumContext $context) => $context->creating())
                ->set(function (Flag $flag, ?Post $post, FlarumContext $context) {
                    if ($post === null) {
                        $flag->post_id = null;

                        return;
                    }

                    if (! ($post instanceof CommentPost)) {
                        throw new InvalidParameterException;
                    }

                    $actor = $context->getActor();

                    $actor->assertCan('flag', $post);

                    if ($actor->id === $post->user_id && ! $this->settings->get('flarum-flags.can_flag_own')) {
                        throw new PermissionDeniedException;
                    }

                    $flag->post_id = $post->id;
                }),
            Schema\Relationship\ToOne::make('user')
                ->includable(),
            Schema\Relationship\ToOne::make('targetUser')
                ->type('users')
                ->includable()
                ->writable(fn (Flag $flag, FlarumContext $context) => $context->creating())
                ->set(function (Flag $flag, ?User $target, FlarumContext $context) {
                    if ($target === null) {
                        $flag->target_user_id = null;

                        return;
                    }

                    $this->userFlagger->assertCanFlag($target, $context->getActor());
                    $flag->target_user_id = $target->id;
                }),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function created(object $model, Context $context): ?object
    {
        if ($model->target_user_id && ! $model->wasRecentlyCreated) {
            return parent::created($model, $context);
        }

        $event = $model->target_user_id
            ? new UserFlagCreated($model, $context->getActor(), $context->body())
            : new Created($model, $context->getActor(), $context->body());
        $this->events->dispatch($event);

        return parent::created($model, $context);
    }
}
