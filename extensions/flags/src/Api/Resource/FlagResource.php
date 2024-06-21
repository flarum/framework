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
use Flarum\Flags\Flag;
use Flarum\Http\Exception\InvalidParameterException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
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
                fn (Builder $query) => $query->distinct('post_id')->orderBy('post_id'),
                else: fn (Builder $query) => $query->groupBy('post_id')
            );

            $this->scope($query, $context);

            return $query;
        }

        return parent::query($context);
    }

    public function scope(Builder $query, Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function newModel(Context $context): object
    {
        if ($context->creating(self::class)) {
            Flag::unguard();

            return Flag::query()->firstOrNew([
                'post_id' => (int) Arr::get($context->body(), 'data.relationships.post.data.id'),
                'user_id' => $context->getActor()->id
            ], [
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
                ->defaultInclude(['post', 'post.flags', 'user']),
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'post', 'post.user', 'post.discussion'])
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
                ->requiredOnCreateWithout(['reasonDetail'])
                ->validationMessages([
                    'reason.required_without' => $this->translator->trans('flarum-flags.forum.flag_post.reason_missing_message'),
                ]),
            Schema\Str::make('reasonDetail')
                ->writableOnCreate()
                ->nullable()
                ->requiredOnCreateWithout(['reason'])
                ->validationMessages([
                    'reasonDetail.required_without' => $this->translator->trans('flarum-flags.forum.flag_post.reason_missing_message'),
                ]),
            Schema\DateTime::make('createdAt'),

            Schema\Relationship\ToOne::make('post')
                ->includable()
                ->writable(fn (Flag $flag, FlarumContext $context) => $context->creating())
                ->set(function (Flag $flag, Post $post, FlarumContext $context) {
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
        $this->events->dispatch(new Created($model, $context->getActor(), $context->body()));

        return parent::created($model, $context);
    }
}
