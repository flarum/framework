<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\PackageManager\Task\Task;
use InvalidArgumentException;

class TaskSerializer extends AbstractSerializer
{
    protected $type = 'package-manager-tasks';

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Task)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Task::class
            );
        }

        return [
            'status' => $model->status,
            'operation' => $model->operation,
            'command' => $model->command,
            'package' => $model->package,
            'output' => $model->output,
            'createdAt' => $model->created_at,
            'startedAt' => $model->started_at,
            'finishedAt' => $model->finished_at,
            'peakMemoryUsed' => $model->peak_memory_used,
        ];
    }
}
