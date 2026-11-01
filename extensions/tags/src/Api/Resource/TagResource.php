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
use Illuminate\Database\Eloquent\Relations\Relation;
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

        $query = $this->query($context);

        if ($this->idCanBeKey($query->getModel(), $id) && $tag = $query->find($id)) {
            return $tag;
        }

        return $this->slugManager->forResource(Tag::class)->fromSlug($id, $actor);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->eagerLoad(['parent'])
                ->eagerLoadWhenIncluded($this->relationsTheIncludesRead()),
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
                ->defaultInclude(['parent'])
                ->eagerLoad(['parent'])
                ->eagerLoadWhenIncluded($this->relationsTheIncludesRead())
                // Listing finds tags with the tag searcher, not scope(), which
                // is where the actor's state is loaded: load it here instead.
                ->eagerLoadWhere('state', function (Builder|Relation $query, FlarumContext $context) {
                    $query->where('user_id', $context->getActor()->id);
                }),
        ];
    }

    /**
     * Serializing an included discussion reads its tags and their parents (for
     * permission checks) and the actor's read state. Load those for every
     * discussion in the response together, rather than once per row. (A tag's
     * own parent is loaded above, and its children get theirs from the
     * inverse relation.).
     */
    protected function relationsTheIncludesRead(): array
    {
        return [
            'lastPostedDiscussion' => ['lastPostedDiscussion.state', 'lastPostedDiscussion.tags.parent'],
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
                ->description('The slug used in the tag\'s URL, and the value the discussions tag filter takes.')
                ->requiredOnCreate()
                ->writable()
                ->unique('tags', 'slug', true)
                ->regex('/^[^\/\\ ]*$/i')
                ->get(function (Tag $tag) {
                    return $this->slugManager->forResource($tag::class)->toSlug($tag);
                }),
            Schema\Str::make('storedSlug')
                ->get(fn (Tag $tag) => $tag->slug)
                ->visible(fn (Tag $tag, FlarumContext $context) => $context->getActor()->can('edit', $tag)),
            Schema\Str::make('color')
                ->description('Hex color code, or null.')
                ->writable()
                ->nullable()
                ->rule('hex_color'),
            Schema\Str::make('icon')
                ->description('Font Awesome icon classes, or null.')
                ->writable()
                ->nullable(),
            Schema\Boolean::make('isHidden')
                ->description('Whether discussions with this tag are left out of the All Discussions list.')
                ->writable(),
            Schema\Boolean::make('isPrimary')
                ->description('Whether this is a primary tag. Discussions need a number of primary tags and may have secondary ones.')
                ->writable(),
            Schema\Boolean::make('isRestricted')
                ->description('Whether the tag has its own permissions instead of the global ones. Only visible to admins.')
                ->writableOnUpdate()
                ->visible(fn (Tag $tag, FlarumContext $context) => $context->getActor()->isAdmin()),
            Schema\Integer::make('discussionCount'),
            Schema\Integer::make('position')
                ->description('Order among the primary tags; null for secondary tags.')
                ->nullable(),
            Schema\Str::make('defaultSort')
                ->description('The sort the tag\'s discussion list opens with, as its URL alias (such as latest), or null.')
                // The sort a tag's discussion list opens with, stored as the
                // alias that appears in a URL rather than the API sort behind
                // it, so that it round-trips through the dropdown unchanged.
                //
                // Deliberately unvalidated against the sorts currently
                // registered: they come and go with the extensions that provide
                // them, and a value pointing at one that is temporarily absent
                // should be kept rather than rejected. The forum falls back to
                // its usual sort while that is the case.
                ->writable()
                ->nullable(),
            Schema\Boolean::make('isChild')
                ->description('Whether the tag is a child of a primary tag.')
                ->get(fn (Tag $tag) => (bool) $tag->parent_id),
            Schema\DateTime::make('lastPostedAt'),
            Schema\Boolean::make('canStartDiscussion')
                ->description('Whether you can start a discussion with this tag.')
                ->get(fn (Tag $tag, FlarumContext $context) => $context->getActor()->can('startDiscussion', $tag)),
            Schema\Boolean::make('canAddToDiscussion')
                ->description('Whether you can add this tag to an existing discussion.')
                ->get(fn (Tag $tag, FlarumContext $context) => $context->getActor()->can('addToDiscussion', $tag)),

            Schema\Relationship\ToOne::make('parent')
                ->description('The parent primary tag of a child tag.')
                ->type('tags')
                ->includable()
                ->writable(fn (Tag $tag, FlarumContext $context) => (bool) Arr::get($context->body(), 'attributes.isPrimary')),
            Schema\Relationship\ToMany::make('children')
                ->type('tags')
                ->includable()
                // A child's parent is the tag it was loaded for, which its
                // permission checks then read without querying it again.
                ->inverse('parent'),
            Schema\Relationship\ToOne::make('lastPostedDiscussion')
                ->description('The discussion with the latest post in this tag.')
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
