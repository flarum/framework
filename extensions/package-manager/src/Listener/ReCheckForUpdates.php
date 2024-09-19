<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Listener;

use Flarum\Bus\Dispatcher;
use Flarum\ExtensionManager\Command\CheckForUpdates;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\ExtensionManager\Extension\Event\Updated;
use Flarum\ExtensionManager\Settings\LastUpdateCheck;
use Flarum\ExtensionManager\Settings\LastUpdateRun;

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
