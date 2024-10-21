<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Api\Resource;

use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Http\SlugManager;
use Flarum\Tags\Event\Creating;
use Flarum\Tags\Event\Deleting;
use Flarum\Tags\Event\Saving;
use Flarum\Tags\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Tag>
 */
class TagResource extends AbstractDatabaseResource
{
    public function __construct(
        protected SlugManager $slugManager
    ) {
    }

    public function type(): string
    {
        return 'tags';
    }

    public function model(): string
    {
        return Tag::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        $query->whereVisibleTo($context->getActor());

        if ($context->listing(self::class) || $context->showing(self::class)) {
            $query->withStateFor($context->getActor());
        }
    }

    public function find(string $id, Context $context): ?object
    {
        $actor = $context->getActor();

        if (is_numeric($id) && $tag = $this->query($context)->find($id)) {
            return $tag;
        }

        return $this->slugManager->forResource(Tag::class)->fromSlug($id, $actor);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make(),
            Endpoint\Create::make()
                ->authenticated()
                ->can('createTag'),
            Endpoint\Update::make()
                ->authenticated()
                ->can('edit'),
            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete'),
            Endpoint\Index::make()
                ->defaultInclude(['parent']),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('name')
                ->requiredOnCreate()
                ->writable(),
            Schema\Str::make('description')
                ->writable()
                ->maxLength(700)
                ->nullable(),
            Schema\Str::make('slug')
                ->requiredOnCreate()
                ->writable()
                ->unique('tags', 'slug', true)
                ->regex('/^[^\/\\ ]*$/i')
                ->get(function (Tag $tag) {
                    return $this->slugManager->forResource($tag::class)->toSlug($tag);
                }),
            Schema\Str::make('color')
                ->writable()
                ->nullable()
                ->rule('hex_color'),
            Schema\Str::make('icon')
                ->writable()
                ->nullable(),
            Schema\Boolean::make('isHidden')
                ->writable(),
            Schema\Boolean::make('isPrimary')
                ->writable(),
            Schema\Boolean::make('isRestricted')
                ->writableOnUpdate()
                ->visible(fn (Tag $tag, FlarumContext $context) => $context->getActor()->isAdmin()),
            Schema\Str::make('backgroundUrl')
                ->get(fn (Tag $tag) => $tag->background_path),
            Schema\Str::make('backgroundMode'),
            Schema\Integer::make('discussionCount'),
            Schema\Integer::make('position')
                ->nullable(),
            Schema\Str::make('defaultSort')
                ->nullable(),
            Schema\Boolean::make('isChild')
                ->get(fn (Tag $tag) => (bool) $tag->parent_id),
            Schema\DateTime::make('lastPostedAt'),
            Schema\Boolean::make('canStartDiscussion')
                ->get(fn (Tag $tag, FlarumContext $context) => $context->getActor()->can('startDiscussion', $tag)),
            Schema\Boolean::make('canAddToDiscussion')
                ->get(fn (Tag $tag, FlarumContext $context) => $context->getActor()->can('addToDiscussion', $tag)),

            Schema\Relationship\ToOne::make('parent')
                ->type('tags')
                ->includable()
                ->writable(fn (Tag $tag, FlarumContext $context) => (bool) Arr::get($context->body(), 'attributes.isPrimary')),
            Schema\Relationship\ToMany::make('children')
                ->type('tags')
                ->includable(),
            Schema\Relationship\ToOne::make('lastPostedDiscussion')
                ->type('discussions')
                ->includable(),
        ];
    }

    public function creating(object $model, Context $context): ?object
    {
        $this->events->dispatch(
            new Creating($model, $context->getActor(), $context->body())
        );

        return $model;
    }

    public function saving(object $model, Context $context): ?object
    {
        if (! $context->creating(self::class)) {
            $this->events->dispatch(
                new Saving($model, $context->getActor(), $context->body())
            );
        }

        return $model;
    }

    public function deleting(object $model, Context $context): void
    {
        $this->events->dispatch(new Deleting($model, $context->getActor()));
    }
}
