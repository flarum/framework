<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Task;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class TaskRepository
{
    /**
     * @return Builder
     */
    public function query()
    {
        return Task::query();
    }

    /**
     * @param int $id
     * @param User $actor
     * @return Task
     */
    public function findOrFail($id, User $actor = null): Task
    {
        return Task::findOrFail($id);
    }
}
