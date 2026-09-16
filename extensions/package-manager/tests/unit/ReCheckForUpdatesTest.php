<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\unit;

use Flarum\Bus\Dispatcher;
use Flarum\Extension\Extension;
use Flarum\ExtensionManager\Command\CheckForUpdates;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\ExtensionManager\Extension\Event\Updated;
use Flarum\ExtensionManager\Listener\ReCheckForUpdates;
use Flarum\ExtensionManager\Settings\LastUpdateRun;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReCheckForUpdatesTest extends TestCase
{
    #[Test]
    #[DataProvider('coreUpdateTypes')]
    public function core_update_records_success_without_rechecking_for_updates(string $updateType): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->expects($this->once())
            ->method('set')
            ->with(LastUpdateRun::key(), $this->callback(function (string $value) use ($updateType): bool {
                $lastUpdateRun = json_decode($value, true)[$updateType];

                return $lastUpdateRun['status'] === LastUpdateRun::SUCCESS
                    && $lastUpdateRun['limitedPackages'] === [];
            }));

        $bus = $this->createMock(Dispatcher::class);
        $bus->expects($this->never())->method('dispatch');

        $listener = new ReCheckForUpdates(new LastUpdateRun($settings), $bus);
        $listener->handle(new FlarumUpdated(new User(), $updateType));
    }

    public static function coreUpdateTypes(): array
    {
        return [
            'global update' => [FlarumUpdated::GLOBAL],
            'minor update' => [FlarumUpdated::MINOR],
            'major update' => [FlarumUpdated::MAJOR],
        ];
    }

    #[Test]
    public function extension_update_still_rechecks_for_updates(): void
    {
        $actor = new User();
        $bus = $this->createMock(Dispatcher::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (CheckForUpdates $command) use ($actor): bool {
                return $command->actor === $actor;
            }))
            ->willReturn([]);

        $listener = new ReCheckForUpdates(
            $this->createStub(LastUpdateRun::class),
            $bus
        );
        $listener->handle(new Updated($actor, new Extension(__DIR__, ['name' => 'acme/example'])));
    }
}
