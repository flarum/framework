<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Realtime\Websocket\Console\ServeCommand;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class DaemonPermissionCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                ['id' => 10, 'username' => 'daemon10', 'email' => 'daemon10@machine.local', 'is_email_confirmed' => 1, 'password' => 'foobar'],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Five', 'name_plural' => 'Fives'],
            ],
            'group_user' => [['user_id' => 10, 'group_id' => 5]],
            Permission::class => [['group_id' => 5, 'permission' => 'custom.five']],
        ]);
    }

    private function hasPermission(): bool
    {
        return User::query()->findOrFail(10)->hasPermission('custom.five');
    }

    #[Test]
    public function the_daemon_sees_a_permission_change_made_elsewhere_once_its_timer_fires(): void
    {
        $container = $this->app()->getContainer();

        /** @var callable[] $timers */
        $timers = [];
        $loop = $this->createStub(LoopInterface::class);
        $loop->method('addPeriodicTimer')->willReturnCallback(function (float $interval, callable $callback) use (&$timers) {
            $timers[] = $callback;

            return $this->createStub(TimerInterface::class);
        });

        // The daemon's other timers are left out, so the only one registered is the permission refresh.
        $command = new class extends ServeCommand {
            public function timers(LoopInterface $loop, Repository $cache): void
            {
                $this->registerTimers($loop, $cache);
            }

            protected function restartOnCachedSignal(LoopInterface $loop, Repository $cache): void
            {
            }

            protected function restartOnExtensionChanges(LoopInterface $loop): void
            {
            }

            protected function sweepIndexTyping(LoopInterface $loop): void
            {
            }

            protected function broadcastAssetsRevisionOnStartup(LoopInterface $loop): void
            {
            }
        };
        $command->setLaravel($container);
        $command->timers($loop, $container->make(Repository::class));

        $this->assertCount(1, $timers);
        $this->assertTrue($this->hasPermission());

        // Written behind the app's back, as another process's change looks to the daemon.
        $this->database()->table('group_permission')->where('permission', 'custom.five')->delete();

        $this->assertTrue($this->hasPermission(), 'precondition: the cache outlives the change until the timer fires');

        $timers[0]($this->createStub(TimerInterface::class));

        $this->assertFalse($this->hasPermission());
    }
}
