<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Task;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;

/**
 * @property int $id
 * @property Status $status
 * @property Operation $operation
 * @property string $command
 * @property string $package
 * @property string $output
 * @property Carbon $created_at
 * @property Carbon $started_at
 * @property Carbon $finished_at
 * @property float $peak_memory_used
 */
class Task extends AbstractModel
{
    public const UPDATED_AT = null;

    protected $table = 'package_manager_tasks';

    protected $fillable = ['command', 'output'];

    public $timestamps = true;

    protected $casts = [
        self::CREATED_AT => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'status' => Status::class,
        'operation' => Operation::class,
    ];

    public static function build(Operation $operation, ?string $package): self
    {
        $task = new static;

        $task->operation = $operation;
        $task->package = $package;
        $task->status = Status::PENDING;
        $task->created_at = Carbon::now();

        $task->save();

        return $task;
    }

    public function start(): bool
    {
        $this->status = Status::RUNNING;
        $this->started_at = Carbon::now();

        return $this->save();
    }

    public function end(bool $success): bool
    {
        $this->status = $success ? Status::SUCCESS : Status::FAILURE;
        $this->finished_at = Carbon::now();
        $this->peak_memory_used = round(memory_get_peak_usage() / 1024);

        return $this->save();
    }
}
