<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Listener;

use Flarum\Bus\Dispatcher;
use Flarum\PackageManager\Command\CheckForUpdates;
use Flarum\PackageManager\Event\FlarumUpdated;
use Flarum\PackageManager\Extension\Event\Updated;
use Flarum\PackageManager\Settings\LastUpdateCheck;
use Flarum\PackageManager\Settings\LastUpdateRun;

class ReCheckForUpdates
{
    public function __construct(
        private readonly LastUpdateRun $lastUpdateRun,
        private readonly LastUpdateCheck $lastUpdateCheck,
        private readonly Dispatcher $bus
    ) {
    }

    public function handle(FlarumUpdated|Updated $event): void
    {
        $previousUpdateCheck = $this->lastUpdateCheck->get();

        $lastUpdateCheck = $this->bus->dispatch(
            new CheckForUpdates($event->actor)
        );

        if ($event instanceof FlarumUpdated) {
            $mapPackageName = fn (array $package) => $package['name'];

            $previousPackages = array_map($mapPackageName, $previousUpdateCheck['updates']['installed']);
            $lastPackages = array_map($mapPackageName, $lastUpdateCheck['updates']['installed']);

            $this->lastUpdateRun
                ->for($event->type)
                ->with('status', LastUpdateRun::SUCCESS)
                ->with('limitedPackages', array_intersect($previousPackages, $lastPackages))
                ->save();
        }
    }
}
