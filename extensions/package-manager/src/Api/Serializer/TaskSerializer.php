<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\ExtensionManager\Task\Task;
use InvalidArgumentException;

class TaskSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'extension-manager-tasks';

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
            'guessedCause' => $model->guessed_cause,
            'createdAt' => $model->created_at,
            'startedAt' => $model->started_at,
            'finishedAt' => $model->finished_at,
            'peakMemoryUsed' => $model->peak_memory_used,
        ];
    }
}
