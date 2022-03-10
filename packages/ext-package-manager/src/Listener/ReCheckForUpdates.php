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
    /**
     * @var LastUpdateRun
     */
    private $lastUpdateRun;
    /**
     * @var LastUpdateCheck
     */
    private $lastUpdateCheck;

    /**
     * @var Dispatcher
     */
    private $bus;

    public function __construct(LastUpdateRun $lastUpdateRun, LastUpdateCheck $lastUpdateCheck, Dispatcher $bus)
    {
        $this->lastUpdateRun = $lastUpdateRun;
        $this->lastUpdateCheck = $lastUpdateCheck;
        $this->bus = $bus;
    }

    /**
     * @param FlarumUpdated|Updated $event
     */
    public function handle($event): void
    {
        $previousUpdateCheck = $this->lastUpdateCheck->get();

        $lastUpdateCheck = $this->bus->dispatch(
            new CheckForUpdates($event->actor)
        );

        if ($event instanceof FlarumUpdated) {
            $mapPackageName = function (array $package) {
                return $package['name'];
            };

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
