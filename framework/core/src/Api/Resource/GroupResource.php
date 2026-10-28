<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context as FlarumContext;
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
        // Writable when creating (the Create endpoint checks `createGroup`) or when the actor
        // may edit the group, so the descriptor offers no input to anyone else.
        $writable = fn (Group $group, FlarumContext $context) => $context->creating() || $context->getActor()->can('edit', $group);

        return [
            Schema\Str::make('nameSingular')
                ->requiredOnCreate()
                ->get(function (Group $group) {
                    return $this->translateGroupName($group->name_singular);
                })
                ->set(function (Group $group, $value) {
                    $group->rename($value, null);
                })
                ->writable($writable)
                // Present names may not be empty; an update need not send them.
                ->rule('filled')
                ->ui(['column' => 90, 'label' => 'core.admin.groups.fields.name_singular', 'placeholder' => 'core.admin.edit_group.singular_placeholder']),
            Schema\Str::make('namePlural')
                ->requiredOnCreate()
                ->get(function (Group $group) {
                    return $this->translateGroupName($group->name_plural);
                })
                ->set(function (Group $group, $value) {
                    $group->rename(null, $value);
                })
                ->writable($writable)
                // Present names may not be empty; an update need not send them.
                ->rule('filled')
                ->ui(['column' => 85, 'label' => 'core.admin.groups.fields.name_plural', 'placeholder' => 'core.admin.edit_group.plural_placeholder']),
            Schema\Str::make('color')
                ->nullable()
                ->writable($writable)
                ->ui(['widget' => 'color', 'label' => 'core.admin.groups.fields.color', 'placeholder' => '#aaaaaa']),
            Schema\Str::make('icon')
                ->nullable()
                ->writable($writable)
                ->ui(['widget' => 'icon', 'label' => 'core.admin.groups.fields.icon', 'placeholder' => 'fas fa-bolt']),
            Schema\Boolean::make('isHidden')
                ->writable($writable)
                ->ui(['column' => 60, 'label' => 'core.admin.groups.fields.is_hidden']),
            Schema\Integer::make('position')
                ->nullable(),

            Schema\Boolean::make('canEdit')
                ->get(fn (Group $group, FlarumContext $context) => $context->getActor()->can('edit', $group)),
            Schema\Boolean::make('canDelete')
                ->get(fn (Group $group, FlarumContext $context) => $context->getActor()->can('delete', $group)),
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
