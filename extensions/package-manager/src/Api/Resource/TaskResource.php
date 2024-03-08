<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\ExtensionManager\Task\Task;

class TaskResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'package-manager-tasks';
    }

    public function model(): string
    {
        return Task::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->defaultSort('-createdAt')
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('status'),
            Schema\Str::make('operation'),
            Schema\Str::make('command'),
            Schema\Str::make('package'),
            Schema\Str::make('output'),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('startedAt'),
            Schema\DateTime::make('finishedAt'),
            Schema\Number::make('peakMemoryUsed'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }
}
