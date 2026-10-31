<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Group\Event\Deleting;
use Flarum\Group\Event\Saving;
use Flarum\Group\Group;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Group>
 */
class GroupResource extends AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function type(): string
    {
        return 'groups';
    }

    public function model(): string
    {
        return Group::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->can('createGroup'),
            Endpoint\Update::make()
                ->authenticated()
                ->can('edit'),
            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete'),
            Endpoint\Show::make(),
            Endpoint\Index::make(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('nameSingular')
                ->description('Name for one member, such as Moderator.')
                ->requiredOnCreate()
                ->get(function (Group $group) {
                    return $this->translateGroupName($group->name_singular);
                })
                ->set(function (Group $group, $value) {
                    $group->rename($value, null);
                })
                ->writable()
                ->required(),
            Schema\Str::make('namePlural')
                ->description('Name for several members, such as Moderators.')
                ->requiredOnCreate()
                ->get(function (Group $group) {
                    return $this->translateGroupName($group->name_plural);
                })
                ->set(function (Group $group, $value) {
                    $group->rename(null, $value);
                })
                ->writable()
                ->required(),
            Schema\Str::make('color')
                ->description('Badge color as a hex code, or null.')
                ->nullable()
                ->writable(),
            Schema\Str::make('icon')
                ->description('Font Awesome icon classes for the badge, such as fas fa-bolt, or null.')
                ->nullable()
                ->writable(),
            Schema\Boolean::make('isHidden')
                ->description('Whether the group is hidden from user profiles and lists.')
                ->writable(),
            Schema\Integer::make('position')
                ->nullable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('nameSingular'),
            SortColumn::make('namePlural'),
            SortColumn::make('isHidden'),
        ];
    }

    private function translateGroupName(string $name): string
    {
        $translation = $this->translator->trans($key = 'core.group.'.strtolower($name));

        if ($translation !== $key) {
            return $translation;
        }

        return $name;
    }

    public function saving(object $model, Context $context): ?object
    {
        $this->events->dispatch(
            new Saving($model, $context->getActor(), Arr::get($context->body(), 'data', []))
        );

        return $model;
    }

    public function deleting(object $model, Context $context): void
    {
        $this->events->dispatch(
            new Deleting($model, $context->getActor(), [])
        );

        parent::deleting($model, $context);
    }
}
