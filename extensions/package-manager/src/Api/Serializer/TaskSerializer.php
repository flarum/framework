<?php

namespace Flarum\PackageManager\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\PackageManager\Task\Task;
use InvalidArgumentException;

class TaskSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'package-manager-tasks';

    /**
     * {@inheritdoc}
     *
     * @param Task $model
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($model)
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
        ];
    }
}
