<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Queue\AbstractJob;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use PHPUnit\Framework\Attributes\Test;

/**
 * The only tag is a restricted primary one, so the verdict turns on group 5's
 * tag permission and on whether the user is in group 5.
 */
class GlobalPolicyWorkerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
        $this->config('queue', ['driver' => 'database']);

        $this->prepareDatabase([
            'tags' => [
                ['id' => 1, 'name' => 'Primary', 'slug' => 'primary', 'position' => 0, 'parent_id' => null, 'is_restricted' => true],
            ],
            User::class => [
                ['id' => 10, 'username' => 'worker10', 'email' => 'worker10@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Five', 'name_plural' => 'Fives'],
            ],
            'group_user' => [['user_id' => 10, 'group_id' => 5]],
            Permission::class => [['group_id' => 5, 'permission' => 'tag1.viewForum']],
        ]);
    }

    /**
     * Set explicitly: tags' migration seeds the defaults after the settings
     * cache is warm on a fresh test database.
     */
    private function requirePrimaryTags(int $count): void
    {
        $this->setting('flarum-tags.min_primary_tags', (string) $count);
        $this->setting('flarum-tags.min_secondary_tags', '0');
    }

    /**
     * Successive jobs share this app, and its policies, as in a long-running worker.
     */
    private function canViewForum(): bool
    {
        $container = $this->app()->getContainer();
        RecordViewForumJob::$result = null;

        $container->make(Queue::class)->push(new RecordViewForumJob(10));
        $this->assertSame(1, $this->database()->table('queue_jobs')->count(), 'the job is queued, not run inline');

        $container->make(Worker::class)->runNextJob('flarum', 'default', new WorkerOptions(sleep: 0, maxTries: 1));
        $this->assertSame(0, $this->database()->table('queue_jobs')->count(), 'the worker ran the job');
        $this->assertNotNull(RecordViewForumJob::$result, 'the job completed');

        return RecordViewForumJob::$result;
    }

    #[Test]
    public function the_next_job_sees_a_revoked_tag_permission()
    {
        $this->requirePrimaryTags(1);

        $this->assertTrue($this->canViewForum());

        // Written behind the app's back, as another process's change looks to the worker.
        $this->database()->table('group_permission')->where('permission', 'tag1.viewForum')->delete();

        $this->assertFalse($this->canViewForum());
    }

    #[Test]
    public function the_next_job_sees_a_user_removed_from_the_group()
    {
        $this->requirePrimaryTags(1);

        $this->assertTrue($this->canViewForum());

        // Written behind the app's back, as another process's change looks to the worker.
        $this->database()->table('group_user')->where('user_id', 10)->delete();

        $this->assertFalse($this->canViewForum());
    }

    #[Test]
    public function the_next_job_sees_a_granted_tag_permission()
    {
        $this->requirePrimaryTags(1);
        $this->database()->table('group_permission')->where('permission', 'tag1.viewForum')->delete();

        $this->assertFalse($this->canViewForum());

        // Written behind the app's back, as another process's change looks to the worker.
        $this->database()->table('group_permission')->insert(['group_id' => 5, 'permission' => 'tag1.viewForum']);

        $this->assertTrue($this->canViewForum());
    }

    #[Test]
    public function with_no_minimum_the_next_job_sees_a_revoked_tag_permission()
    {
        $this->requirePrimaryTags(0);
        // Otherwise the global permission answers for everyone.
        $this->database()->table('group_permission')->where('permission', 'viewForum')->delete();

        $this->assertTrue($this->canViewForum());

        // Written behind the app's back, as another process's change looks to the worker.
        $this->database()->table('group_permission')->where('permission', 'tag1.viewForum')->delete();

        $this->assertFalse($this->canViewForum());
    }
}

class RecordViewForumJob extends AbstractJob
{
    public static ?bool $result = null;

    public function __construct(private int $userId)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        static::$result = User::query()->findOrFail($this->userId)->can('viewForum');
    }
}
