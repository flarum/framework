<?php

namespace Flarum\Tags\Api\Resource;

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

        if ($context->collection instanceof self && (
            $context->endpoint instanceof Endpoint\Index
            || $context->endpoint instanceof Endpoint\Show
        )) {
            $query->withStateFor($context->getActor());
        }
    }

    public function find(string $id, \Tobyz\JsonApiServer\Context $context): ?object
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
                ->regex('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'),
            Schema\Str::make('icon')
                ->writable()
                ->nullable(),
            Schema\Boolean::make('isHidden')
                ->writable(),
            Schema\Boolean::make('isPrimary')
                ->writable(),
            Schema\Boolean::make('isRestricted')
                ->writableOnUpdate()
                ->visible(fn (Tag $tag, Context $context) => $context->getActor()->isAdmin()),
            Schema\Str::make('backgroundUrl')
                ->get(fn (Tag $tag) => $tag->background_path),
            Schema\Str::make('backgroundMode'),
            Schema\Integer::make('discussionCount'),
            Schema\Integer::make('position')
                ->nullable(),
            Schema\Str::make('defaultSort'),
            Schema\Boolean::make('isChild')
                ->get(fn (Tag $tag) => (bool) $tag->parent_id),
            Schema\DateTime::make('lastPostedAt'),
            Schema\Boolean::make('canStartDiscussion')
                ->get(fn (Tag $tag, Context $context) => $context->getActor()->can('startDiscussion', $tag)),
            Schema\Boolean::make('canAddToDiscussion')
                ->get(fn (Tag $tag, Context $context) => $context->getActor()->can('addToDiscussion', $tag)),

            Schema\Relationship\ToOne::make('parent')
                ->type('tags')
                ->includable()
                ->writable(fn (Tag $tag, Context $context) => (bool) Arr::get($context->body(), 'attributes.isPrimary')),
            Schema\Relationship\ToMany::make('children')
                ->type('tags')
                ->includable(),
            Schema\Relationship\ToOne::make('lastPostedDiscussion')
                ->type('discussions')
                ->includable(),
        ];
    }

    protected function newSavingEvent(Context $context, array $data): ?object
    {
        return $context->endpoint instanceof Endpoint\Create
            ? new Creating($context->model, $context->getActor(), $data)
            : new Saving($context->model, $context->getActor(), $data);
    }

    public function deleting(object $model, Context $context): void
    {
        $this->events->dispatch(new Deleting($model, $context->getActor()));
    }
}
