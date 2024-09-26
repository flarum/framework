<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Listener;

use Composer\Command\ClearCacheCommand;
use Flarum\Database\Console\MigrateCommand;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\Foundation\Console\AssetsPublishCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ClearCacheAfterUpdate
{
    /**
     * @var ClearCacheCommand
     */
    private $clearCache;

    /**
     * @var AssetsPublishCommand
     */
    private $publishAssets;

    /**
     * @var MigrateCommand
     */
    private $migrate;

    public function __construct(ClearCacheCommand $clearCache, AssetsPublishCommand $publishAssets, MigrateCommand $migrate)
    {
        $this->clearCache = $clearCache;
        $this->publishAssets = $publishAssets;
        $this->migrate = $migrate;
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
