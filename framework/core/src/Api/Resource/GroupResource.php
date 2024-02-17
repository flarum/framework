<?php

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Group\Event\Deleting;
use Flarum\Group\Event\Saving;
use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\Eloquent\Builder;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;

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
                ->nullable()
                ->writable(),
            Schema\Str::make('icon')
                ->nullable()
                ->writable(),
            Schema\Boolean::make('isHidden')
                ->writable(),
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

    protected function newSavingEvent(Context $context, array $data): ?object
    {
        return new Saving($context->model, RequestUtil::getActor($context->request), $data);
    }

    public function deleting(object $model, Context $context): void
    {
        $this->events->dispatch(
            new Deleting($model, $context->getActor(), [])
        );

        parent::deleting($model, $context);
    }
}
