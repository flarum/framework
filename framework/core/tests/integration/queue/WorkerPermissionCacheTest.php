<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Queue\AbstractJob;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use PHPUnit\Framework\Attributes\Test;

class WorkerPermissionCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                ['id' => 10, 'username' => 'worker10', 'email' => 'worker10@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
                ['id' => 11, 'username' => 'worker11', 'email' => 'worker11@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Five', 'name_plural' => 'Fives'],
            ],
            'group_user' => [
                ['user_id' => 10, 'group_id' => 5],
                ['user_id' => 11, 'group_id' => 5],
            ],
            Permission::class => [['group_id' => 5, 'permission' => 'custom.five']],
        ]);

        RecordPermissionsJob::$results = [];
    }

    /**
     * Successive jobs share this app and its permission cache, as in a
     * long-running worker.
     */
    private function work(string $permission, int ...$userIds): array
    {
        $container = $this->app()->getContainer();

        $container->make(Queue::class)->push(new RecordPermissionsJob($permission, $userIds));
        $this->assertSame(1, $this->database()->table('queue_jobs')->count(), 'the job is queued, not run inline');

        $container->make(Worker::class)->runNextJob('flarum', 'default', new WorkerOptions(sleep: 0, maxTries: 1));
        $this->assertSame(0, $this->database()->table('queue_jobs')->count(), 'the worker ran the job');

        return RecordPermissionsJob::$results;
    }

    private function countPermissionQueries(callable $callback): int
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $callback();

        return count(array_filter($db->getQueryLog(), fn (array $query) => str_contains($query['query'], 'group_permission')));
    }

    #[Test]
    public function the_next_job_sees_a_permission_revoked_by_another_process()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->assertSame([10 => true], $this->work('custom.five', 10));

        // Written behind the app's back, as another process's change looks to the worker.
        $this->database()->table('group_permission')->where('permission', 'custom.five')->delete();

        $this->assertSame([10 => false], $this->work('custom.five', 10));
    }

    #[Test]
    public function the_next_job_sees_a_permission_granted_by_another_process()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->assertSame([10 => false], $this->work('custom.new', 10));

        // Written behind the app's back, as another process's change looks to the worker.
        $this->database()->table('group_permission')->insert(['group_id' => 5, 'permission' => 'custom.new']);

        $this->assertSame([10 => true], $this->work('custom.new', 10));
    }

    #[Test]
    public function users_sharing_a_group_set_in_one_job_still_share_one_permission_query()
    {
        $this->config('queue', ['driver' => 'database']);

        $this->work('custom.five', 10);

        $queries = $this->countPermissionQueries(function () {
            $this->assertSame([10 => true, 11 => true], $this->work('custom.five', 10, 11));
        });

        $this->assertSame(1, $queries);
    }

    #[Test]
    public function jobs_run_inline_on_the_sync_queue_share_the_request_cache()
    {
        $queue = $this->app()->getContainer()->make(Queue::class);

        $queue->push(new RecordPermissionsJob('custom.five', [10]));

        $queries = $this->countPermissionQueries(function () use ($queue) {
            $queue->push(new RecordPermissionsJob('custom.five', [10]));
            $queue->push(new RecordPermissionsJob('custom.five', [11]));
        });

        $this->assertSame([11 => true], RecordPermissionsJob::$results);
        $this->assertSame(0, $queries);
    }
}

class RecordPermissionsJob extends AbstractJob
{
    /** @var array<int, bool> */
    public static array $results = [];

    /**
     * @param int[] $userIds
     */
    public function __construct(private string $permission, private array $userIds)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        static::$results = [];

        foreach ($this->userIds as $id) {
            static::$results[$id] = User::query()->findOrFail($id)->hasPermission($this->permission);
        }
    }
}
