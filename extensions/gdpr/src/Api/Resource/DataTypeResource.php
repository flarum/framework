<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Gdpr\DataProcessor;
use Laminas\Diactoros\Response\JsonResponse;
use stdClass;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends Resource\AbstractResource<object>
 */
class DataTypeResource extends Resource\AbstractResource implements Resource\Contracts\Listable
{
    public function __construct(
        protected DataProcessor $processor
    ) {
    }

    public function routeNamePrefix(): ?string
    {
        return 'gdpr';
    }

    public function type(): string
    {
        return 'gdpr-datatypes';
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->admin(),
            Endpoint\Endpoint::make('user-columns')
                ->route('GET', '/user-columns')
                ->admin()
                ->action(function () {
                    $removableColumns = $this->processor->removableUserColumns();
                    $allColumns = $this->processor->allUserColumns();

                    return compact('removableColumns', 'allColumns');
                })
                ->response(function (array $data) {
                    return new JsonResponse(compact('data'));
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('type')
                ->get(fn (object $model) => $model->class::dataType()),
            Schema\Str::make('exportDescription')
                ->get(fn (object $model) => $model->class::exportDescription()),
            Schema\Str::make('anonymizeDescription')
                ->get(fn (object $model) => $model->class::anonymizeDescription()),
            Schema\Str::make('deleteDescription')
                ->get(fn (object $model) => $model->class::deleteDescription()),
            Schema\Str::make('extension'),
        ];
    }

    public function getId(object $model, OriginalContext $context): string
    {
        return $model->class;
    }

    public function query(OriginalContext $context): object
    {
        return new stdClass();
    }

    public function results(object $query, OriginalContext $context): iterable
    {
        $types = $this->processor->types();

        return array_map(function ($class, $extension) {
            return (object) ['class' => $class, 'extension' => $extension];
        }, array_keys($types), $types);
    }
}
