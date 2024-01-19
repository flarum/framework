<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Listener;

use Composer\Command\ClearCacheCommand;
use Flarum\Database\Console\MigrateCommand;
use Flarum\Foundation\Console\AssetsPublishCommand;
use Flarum\PackageManager\Event\FlarumUpdated;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCacheAfterUpdate
{
    public function __construct(
        private ClearCacheCommand $clearCache,
        private AssetsPublishCommand $publishAssets,
        private MigrateCommand $migrate
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handle(FlarumUpdated $event): void
    {
        $this->clearCache->run(new ArrayInput([]), new NullOutput());
        $this->migrate->run(new ArrayInput([]), new NullOutput());
        $this->publishAssets->run(new ArrayInput([]), new NullOutput());
    }
}
