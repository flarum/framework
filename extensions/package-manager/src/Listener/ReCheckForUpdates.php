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
use Flarum\ExtensionManager\Settings\LastUpdateRun;

class ReCheckForUpdates
{
    /**
     * @var LastUpdateRun
     */
    private $lastUpdateRun;
    /**
     * @var Dispatcher
     */
    private $bus;

    public function __construct(LastUpdateRun $lastUpdateRun, Dispatcher $bus)
    {
        $this->lastUpdateRun = $lastUpdateRun;
        $this->bus = $bus;
    }

    /**
     * @param FlarumUpdated|Updated $event
     */
    public function handle($event): void
    {
        if ($event instanceof FlarumUpdated) {
            // Composer replaced vendor files, so this process's stale autoloader cannot safely load new dependency classes.
            $this->lastUpdateRun
                ->for($event->type)
                ->with('status', LastUpdateRun::SUCCESS)
                ->with('limitedPackages', [])
                ->save();

            return;
        }

        $this->bus->dispatch(
            new CheckForUpdates($event->actor)
        );
    }
}
