<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Task;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;

/**
 * @property int $id
 * @property int $status
 * @property string $operation
 * @property string $command
 * @property string $package
 * @property string $output
 * @property string|null $guessed_cause
 * @property Carbon $created_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property float $peak_memory_used
 */
class Task extends AbstractModel
{
    /**
     * Statuses (@todo use an enum with php8.1).
     */
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const FAILURE = 'failure';
    public const SUCCESS = 'success';

    /**
     * Operations (@todo use an enum with php8.1).
     */
    public const EXTENSION_INSTALL = 'extension_install';
    public const EXTENSION_REMOVE = 'extension_remove';
    public const EXTENSION_UPDATE = 'extension_update';
    public const UPDATE_GLOBAL = 'update_global';
    public const UPDATE_MINOR = 'update_minor';
    public const UPDATE_MAJOR = 'update_major';
    public const UPDATE_CHECK = 'update_check';
    public const WHY_NOT = 'why_not';

    public const UPDATED_AT = null;

    protected $table = 'extension_manager_tasks';

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $casts = [
        self::CREATED_AT => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public static function build(string $operation, ?string $package): self
    {
        $task = new static;

        $task->operation = $operation;
        $task->package = $package;
        $task->status = static::PENDING;
        $task->created_at = Carbon::now();

        $task->save();

        return $task;
    }

    public function start(): bool
    {
        $this->status = static::RUNNING;
        $this->started_at = Carbon::now();

        return $this->save();
    }

    public function end(bool $success): bool
    {
        if ($this->finished_at) {
            return true;
        }

        if (! $this->started_at) {
            $this->start();
        }

        $this->status = $success ? static::SUCCESS : static::FAILURE;
        $this->finished_at = Carbon::now();
        $this->peak_memory_used = round(memory_get_peak_usage() / 1024);

        return $this->save();
    }
}
